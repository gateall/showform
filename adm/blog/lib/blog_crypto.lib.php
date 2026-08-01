<?php
if (!defined('_GNUBOARD_')) exit;

// 블로그 전용 암호화 래퍼 — adm/landing/ai_crypto.php(고정 키·고정 IV, landing 프로젝트와 공유)는
// 건드리지 않는다. site_credentials.cred_value_enc / ai_providers.api_key_enc 저장 경로는
// 이 래퍼만 사용한다.
//
// - 키는 이 파일에 하드코딩하지 않는다. data/dbconfig.php 의 BP_CRYPTO_KEY 상수로 주입하며,
//   그 값은 배포 환경마다 다른 실제 랜덤값이어야 한다.
// - 키가 설정되지 않은 상태에서는 저장을 중단한다(평문·빈 암호문 저장 금지).
// - IV는 호출마다 random_bytes(16)으로 새로 생성해 암호문 앞에 붙여 저장한다(고정 IV 재사용 금지).
// - AI 공급자·API 키를 추가/수정/삭제하는 일상적인 작업은 이 파일이나 dbconfig.php를 전혀
//   건드리지 않는다 — BP_CRYPTO_KEY는 "마스터 키"로 한 번 설정하면 계속 유지하는 값이다.
//   dbconfig.php를 다시 만지는 건 최초 설치, 그리고 아래 키 회전(버전 교체) 시점뿐이다.
//
// 키 버전 관리(크립토 애자일리티):
// - 암호문은 "{version}:{algo}:{base64(iv+cipher)}" 형태로 저장되어, 어떤 키·알고리즘으로
//   암호화됐는지 스스로 설명한다(복호화 시점에 별도 조회 없이 바로 알 수 있음).
// - BP_CRYPTO_KEY는 항상 "현재" 버전의 키다. BP_CRYPTO_CURRENT_VERSION(dbconfig.php, 미정의
//   시 1)이 그 버전 번호를 선언한다.
// - 향후 마스터 키를 교체(회전)할 때만: 기존 값을 BP_CRYPTO_KEY_V{n}이라는 새 상수로 보존하고,
//   BP_CRYPTO_KEY를 새 값으로 바꾸고, BP_CRYPTO_CURRENT_VERSION을 올린다. 그 다음 관리자
//   화면의 재암호화 도구(ai_provider_reencrypt_all.php)를 실행해 기존 암호문을 새 키로
//   일괄 재암호화한다 — 그 작업이 전부 성공적으로 끝난 뒤에만 BP_CRYPTO_KEY_V{old} 상수를
//   지운다(그 전에 지우면 아직 재암호화 안 된 값들이 영구히 복호화 불가 상태가 된다).
// - 버전 프리픽스가 없는 예전 형식의 암호문도 계속 복호화할 수 있어야 한다(이 기능을 넣기
//   전에 이미 저장된 값이 있을 수 있으므로) — bp_decrypt_secret()이 자동으로 구분해 처리한다.

function bp_crypto_key(): string
{
    if (defined('BP_CRYPTO_KEY') && BP_CRYPTO_KEY !== '') {
        return BP_CRYPTO_KEY;
    }
    // 배포 환경에 따라 상수 대신 환경변수로 주입할 수도 있다 — 어느 경로든 키 값 자체는
    // 이 저장소에 커밋되지 않는다.
    $env = getenv('BP_CRYPTO_KEY');
    if ($env !== false && $env !== '') {
        return $env;
    }
    return '';
}

// 현재 BP_CRYPTO_KEY가 몇 번 버전인지. 아직 회전을 한 번도 안 했다면(대부분의 설치) 1로
// 취급한다 - dbconfig.php에 이 상수를 아직 추가하지 않았다고 해서 암호화가 막히면 안 된다.
function bp_crypto_current_version(): int
{
    return defined('BP_CRYPTO_CURRENT_VERSION') ? (int) BP_CRYPTO_CURRENT_VERSION : 1;
}

// 특정 버전 번호에 해당하는 실제 키 값을 찾는다. 현재 버전이면 BP_CRYPTO_KEY(또는 환경변수),
// 그보다 과거 버전이면 회전 작업 중에만 임시로 존재하는 BP_CRYPTO_KEY_V{n} 상수를 찾는다.
// 둘 다 없으면 빈 문자열 - 그 버전으로 암호화된 값은 지금은 복호화할 수 없다는 뜻이다.
function bp_crypto_key_for_version(int $version): string
{
    if ($version === bp_crypto_current_version()) {
        return bp_crypto_key();
    }
    $const_name = 'BP_CRYPTO_KEY_V' . $version;
    if (defined($const_name) && constant($const_name) !== '') {
        return (string) constant($const_name);
    }
    return '';
}

// 임의 길이의 키 문자열을 AES-256에 필요한 32바이트로 고정 변환한다.
function bp_crypto_derive_key(string $raw_key): string
{
    return hash('sha256', $raw_key, true);
}

// 암호문 문자열만 보고 "{version}:{algo}:...}" 프리픽스가 있는지 파싱한다. 없으면(예전
// 형식) null을 반환한다 - encryption_key_version 컬럼을 채울 때 등, 복호화 없이 버전만
// 알고 싶을 때 쓴다.
function bp_crypto_extract_version(string $enc): ?int
{
    if ($enc === '') {
        return null;
    }
    $parts = explode(':', $enc, 3);
    if (count($parts) === 3 && ctype_digit($parts[0])) {
        return (int) $parts[0];
    }
    return null;
}

function bp_encrypt_secret(string $plain): string
{
    if ($plain === '') {
        return '';
    }

    $version = bp_crypto_current_version();
    $key = bp_crypto_key_for_version($version);
    if ($key === '') {
        alert('암호화 키(BP_CRYPTO_KEY)가 설정되지 않아 저장할 수 없습니다. data/dbconfig.php 설정 후 다시 시도해 주세요.');
    }

    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', bp_crypto_derive_key($key), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        alert('암호화에 실패했습니다.');
    }

    return $version . ':aes-256-cbc:' . base64_encode($iv . $cipher);
}

function bp_decrypt_secret(string $enc): string
{
    if ($enc === '') {
        return '';
    }

    $version = bp_crypto_extract_version($enc);

    if ($version !== null) {
        // 신규(버전 있는) 형식
        $algo_and_data = explode(':', $enc, 3);
        $algo = $algo_and_data[1];
        $b64 = $algo_and_data[2];

        if ($algo !== 'aes-256-cbc') {
            return ''; // 알 수 없는 알고리즘 - 시도하지 않는다(향후 알고리즘 추가 시 여기 분기 추가)
        }

        $key = bp_crypto_key_for_version($version);
        if ($key === '') {
            return ''; // 이 버전의 키를 아직 못 찾음(회전 중 구버전 키 상수가 지워졌을 수 있음)
        }

        $raw = base64_decode($b64, true);
    } else {
        // 예전(버전 프리픽스 없는) 형식 - 항상 "현재" 키로 암호화됐던 시절의 값이다.
        $key = bp_crypto_key();
        if ($key === '') {
            return '';
        }
        $raw = base64_decode($enc, true);
    }

    if ($raw === false || strlen($raw) <= 16) {
        return '';
    }

    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', bp_crypto_derive_key($key), OPENSSL_RAW_DATA, $iv);

    return $plain === false ? '' : $plain;
}
