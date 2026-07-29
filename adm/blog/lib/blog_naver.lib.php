<?php
if (!defined('_GNUBOARD_')) exit;

// 네이버 블로그 공식 오픈 API 연동 (Stage 10) — OAuth2 인증 코드 흐름.
// 앱 레벨 client_id/client_secret은 blog_channel_apps(channel_code='naver_blog')에 저장하고,
// 사이트별 access_token/refresh_token은 기존 blog_site_credentials(cred_type='naver_oauth')에
// JSON을 암호화해 저장한다(신규 컬럼 없이 재사용 — 필드 1개에 토큰 2개+만료시각을 담는다).

const BP_NAVER_AUTHORIZE_URL = 'https://nid.naver.com/oauth2.0/authorize';
const BP_NAVER_TOKEN_URL = 'https://nid.naver.com/oauth2.0/token';
const BP_NAVER_BLOG_WRITE_URL = 'https://openapi.naver.com/blog/writePost.json';

function bp_naver_get_app(): ?array
{
    $table = bp_table('channel_apps');
    $row = sql_fetch(" select * from {$table} where channel_code = 'naver_blog' and is_active = 'Y' limit 1 ");
    return $row ? $row : null;
}

function bp_naver_build_authorize_url(string $state): array
{
    $app = bp_naver_get_app();
    if (!$app) {
        return array('ok' => false, 'error' => '네이버 채널 앱이 설정되지 않았습니다. 먼저 채널 앱 설정에서 등록해 주세요.');
    }
    $params = array(
        'response_type' => 'code',
        'client_id' => $app['client_id'],
        'redirect_uri' => $app['redirect_uri'],
        'state' => $state,
    );
    return array('ok' => true, 'url' => BP_NAVER_AUTHORIZE_URL . '?' . http_build_query($params));
}

function bp_naver_curl_transport(string $method, string $url, array $headers, ?string $body): array
{
    if (!function_exists('curl_init')) {
        return array('http_code' => 0, 'body' => '', 'error' => '서버에 curl 확장이 설치되어 있지 않습니다.');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return array('http_code' => (int) $http_code, 'body' => (string) $response, 'error' => $err);
}

// 네이버 토큰 엔드포인트 공통 호출 — $transport를 주입하면 실제 네트워크 호출 없이 테스트할 수 있다.
function bp_naver_call_token_endpoint(array $params, ?callable $transport = null): array
{
    $transport = $transport !== null ? $transport : 'bp_naver_curl_transport';
    $url = BP_NAVER_TOKEN_URL . '?' . http_build_query($params);
    $res = call_user_func($transport, 'GET', $url, array(), null);

    if (!empty($res['error'])) {
        return array('ok' => false, 'error' => '네이버 토큰 요청 실패: ' . $res['error']);
    }
    $body = json_decode((string) $res['body'], true);
    if (!is_array($body) || empty($body['access_token'])) {
        $msg = isset($body['error_description']) ? $body['error_description'] : '토큰 응답을 해석할 수 없습니다.';
        return array('ok' => false, 'error' => $msg);
    }

    $expires_in = isset($body['expires_in']) ? (int) $body['expires_in'] : 3600;
    return array(
        'ok' => true,
        'access_token' => $body['access_token'],
        'refresh_token' => isset($body['refresh_token']) ? $body['refresh_token'] : '',
        'expires_at' => date('Y-m-d H:i:s', time() + $expires_in),
    );
}

function bp_naver_exchange_code(string $code, string $state, ?callable $transport = null): array
{
    $app = bp_naver_get_app();
    if (!$app) {
        return array('ok' => false, 'error' => '네이버 채널 앱이 설정되지 않았습니다.');
    }
    $client_secret = bp_decrypt_secret($app['client_secret_enc']);
    $params = array(
        'grant_type' => 'authorization_code',
        'client_id' => $app['client_id'],
        'client_secret' => $client_secret,
        'code' => $code,
        'state' => $state,
    );
    return bp_naver_call_token_endpoint($params, $transport);
}

function bp_naver_refresh_token(string $refreshToken, ?callable $transport = null): array
{
    $app = bp_naver_get_app();
    if (!$app) {
        return array('ok' => false, 'error' => '네이버 채널 앱이 설정되지 않았습니다.');
    }
    $client_secret = bp_decrypt_secret($app['client_secret_enc']);
    $params = array(
        'grant_type' => 'refresh_token',
        'client_id' => $app['client_id'],
        'client_secret' => $client_secret,
        'refresh_token' => $refreshToken,
    );
    return bp_naver_call_token_endpoint($params, $transport);
}

// 사이트별 토큰 저장(암호화된 JSON 1개 필드) — cred_type='naver_oauth' 고정.
function bp_naver_store_tokens(int $siteId, array $tokenData, string $naverNickname = ''): void
{
    $cred_table = bp_table('site_credentials');
    $json = json_encode(array(
        'access_token' => $tokenData['access_token'],
        'refresh_token' => isset($tokenData['refresh_token']) ? $tokenData['refresh_token'] : '',
        'expires_at' => $tokenData['expires_at'],
    ));
    $enc = bp_encrypt_secret($json);
    $hint = $naverNickname !== '' ? mb_substr($naverNickname, 0, 20) : '연결됨';

    $existing = sql_fetch(" select id from {$cred_table} where site_id = '{$siteId}' and cred_type = 'naver_oauth' ");
    if ($existing) {
        sql_query(" update {$cred_table}
                        set cred_value_enc = '" . sql_real_escape_string($enc) . "',
                            masked_hint = '" . sql_real_escape_string($hint) . "',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '" . (int) $existing['id'] . "' ");
    } else {
        sql_query(" insert into {$cred_table}
                        set site_id = '{$siteId}',
                            cred_type = 'naver_oauth',
                            cred_username = '" . sql_real_escape_string($naverNickname) . "',
                            cred_value_enc = '" . sql_real_escape_string($enc) . "',
                            masked_hint = '" . sql_real_escape_string($hint) . "',
                            created_at = '" . G5_TIME_YMDHIS . "',
                            updated_at = '" . G5_TIME_YMDHIS . "' ");
    }
}

function bp_naver_load_tokens(int $siteId): ?array
{
    $cred_table = bp_table('site_credentials');
    $row = sql_fetch(" select cred_value_enc from {$cred_table} where site_id = '{$siteId}' and cred_type = 'naver_oauth' ");
    if (!$row || empty($row['cred_value_enc'])) {
        return null;
    }
    $json = bp_decrypt_secret($row['cred_value_enc']);
    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['access_token'])) {
        return null;
    }
    return $data;
}

// 만료 임박(5분 이내) 여부까지 고려해 유효한 access_token을 반환한다 — 필요하면 refresh_token으로
// 자동 갱신하고 갱신된 값을 다시 저장한다. 스케줄러가 무인 상태로 호출해도 안전하게 동작해야 한다.
function bp_naver_get_valid_access_token(int $siteId, ?callable $transport = null): array
{
    $tokens = bp_naver_load_tokens($siteId);
    if (!$tokens) {
        return array('ok' => false, 'error' => '이 사이트는 네이버 블로그와 연결되어 있지 않습니다. 먼저 네이버 로그인 연동을 진행해 주세요.');
    }

    $expires_at = isset($tokens['expires_at']) ? strtotime($tokens['expires_at']) : 0;
    if ($expires_at > time() + 300) {
        return array('ok' => true, 'access_token' => $tokens['access_token']);
    }

    if (empty($tokens['refresh_token'])) {
        return array('ok' => false, 'error' => '토큰이 만료되었고 재발급용 refresh_token이 없습니다. 다시 연동해 주세요.');
    }

    $refreshed = bp_naver_refresh_token($tokens['refresh_token'], $transport);
    if (!$refreshed['ok']) {
        return array('ok' => false, 'error' => '토큰 갱신 실패: ' . $refreshed['error']);
    }

    // 네이버는 refresh 응답에 refresh_token을 다시 내려주지 않을 수 있다 — 없으면 기존 값 유지.
    if (empty($refreshed['refresh_token'])) {
        $refreshed['refresh_token'] = $tokens['refresh_token'];
    }
    bp_naver_store_tokens($siteId, $refreshed);

    return array('ok' => true, 'access_token' => $refreshed['access_token']);
}
