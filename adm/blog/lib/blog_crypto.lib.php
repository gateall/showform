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

// 임의 길이의 키 문자열을 AES-256에 필요한 32바이트로 고정 변환한다.
function bp_crypto_derive_key(string $raw_key): string
{
    return hash('sha256', $raw_key, true);
}

function bp_encrypt_secret(string $plain): string
{
    if ($plain === '') {
        return '';
    }

    $key = bp_crypto_key();
    if ($key === '') {
        alert('암호화 키(BP_CRYPTO_KEY)가 설정되지 않아 저장할 수 없습니다. data/dbconfig.php 설정 후 다시 시도해 주세요.');
    }

    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', bp_crypto_derive_key($key), OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        alert('암호화에 실패했습니다.');
    }

    return base64_encode($iv . $cipher);
}

function bp_decrypt_secret(string $enc): string
{
    if ($enc === '') {
        return '';
    }

    $key = bp_crypto_key();
    if ($key === '') {
        return '';
    }

    $raw = base64_decode($enc, true);
    if ($raw === false || strlen($raw) <= 16) {
        return '';
    }

    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', bp_crypto_derive_key($key), OPENSSL_RAW_DATA, $iv);

    return $plain === false ? '' : $plain;
}
