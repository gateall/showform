<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

function editor_html($id, $content, $is_dhtml_editor=true)
{
    global $g5, $config, $w, $board, $write;
    static $js = true;

    if(
        $is_dhtml_editor && $content &&
        (
            (!$w && (isset($board['bo_insert_content']) && !empty($board['bo_insert_content'])))
            || ($w == 'u' && isset($write['wr_option']) && strpos($write['wr_option'], 'html') === false)
        )
    ){ // 글쓰기 기본 내용 처리
        if (preg_match('/\r|\n/', $content) && $content === strip_tags($content, '<a><strong><b>')) { // textarea로 작성되고, html 내용이 없다면
            $content = nl2br($content);
        }
    }

    $editor_url  = G5_EDITOR_URL.'/'.$config['cf_editor'];
    $editor_path = G5_EDITOR_PATH.'/'.$config['cf_editor'];

    // HuskyEZCreator.js 경로 자동 감지
    $husky_js_url = '';
    if (is_file($editor_path.'/js/HuskyEZCreator.js')) {
        $husky_js_url = $editor_url.'/js/HuskyEZCreator.js';
    } elseif (is_file($editor_path.'/js/service/HuskyEZCreator.js')) {
        $husky_js_url = $editor_url.'/js/service/HuskyEZCreator.js';
    } else {
        // 파일 탐지 실패 시 기본 경로 부여 (브라우저에서 직접 로드 시도)
        $husky_js_url = $editor_url.'/js/HuskyEZCreator.js';
    }

    $config_js_url = $editor_url.'/config.js';
    $shortcut_url  = $editor_url.'/shortcut.html';

    // 실제로 웹에디터 사용 가능한지 확인
    $use_dhtml_editor = ($is_dhtml_editor && $husky_js_url && $config_js_url);

    $html = "";
    $html .= "<span class=\"sound_only\">웹에디터 시작</span>";

    if ($use_dhtml_editor) {
        $html .= '<script>document.write("<div class=\'cke_sc\'><button type=\'button\' class=\'btn_cke_sc\'>단축키 일람</button></div>");</script>';
    }

    if ($use_dhtml_editor && $js) {
        $html .= "\n".'<script src="'.$husky_js_url.'"></script>';
        $html .= "\n".'<script>var g5_editor_url = "'.$editor_url.'", oEditors = [], ed_nonce = "'.ft_nonce_create('smarteditor').'";</script>';
        $html .= "\n".'<script src="'.$config_js_url.'"></script>';
        $html .= "\n<script>";
        $html .= '
        $(function(){
            $(".btn_cke_sc").click(function(){
                if ($(this).next("div.cke_sc_def").length) {
                    $(this).next("div.cke_sc_def").remove();
                    $(this).text("단축키 일람");
                } else {';
        if ($shortcut_url) {
            $html .= '
                    $(this).after("<div class=\'cke_sc_def\' />").next("div.cke_sc_def").load("'.$shortcut_url.'");
                    $(this).text("단축키 일람 닫기");';
        } else {
            $html .= '
                    alert("shortcut.html 파일이 없습니다.");
                    return false;';
        }
        $html .= '
                }
            });
            $(document).on("click", ".btn_cke_sc_close", function(){
                $(this).parent("div.cke_sc_def").remove();
                $(".btn_cke_sc").text("단축키 일람");
            });
        });';
        $html .= "\n</script>";
        $js = false;
    }

    $smarteditor_class = $use_dhtml_editor ? "smarteditor2" : "";
    $html .= "\n<textarea id=\"$id\" name=\"$id\" class=\"$smarteditor_class\" maxlength=\"65536\" style=\"width:100%;height:300px\">$content</textarea>";
    $html .= "\n<span class=\"sound_only\">웹 에디터 끝</span>";

    return $html;
}


// textarea 로 값을 넘긴다. javascript 반드시 필요
function get_editor_js($id, $is_dhtml_editor=true)
{
    if ($is_dhtml_editor) {
        return "var {$id}_editor_el = document.getElementById('{$id}');\n"
            ."var {$id}_editor_data = '';\n"
            ."if (typeof oEditors !== 'undefined' && oEditors.getById && oEditors.getById['{$id}']) {\n"
            ."    {$id}_editor_data = oEditors.getById['{$id}'].getIR();\n"
            ."    oEditors.getById['{$id}'].exec('UPDATE_CONTENTS_FIELD', []);\n"
            ."} else if ({$id}_editor_el) {\n"
            ."    {$id}_editor_data = {$id}_editor_el.value;\n"
            ."}\n"
            ."if ({$id}_editor_el && jQuery.inArray({$id}_editor_el.value.toLowerCase().replace(/^\\s*|\\s*$/g, ''), ['&nbsp;','<p>&nbsp;</p>','<p><br></p>','<div><br></div>','<p></p>','<br>','']) != -1) { {$id}_editor_el.value=''; }\n";
    } else {
        return "var {$id}_editor = document.getElementById('{$id}');\n";
    }
}


// textarea 의 값이 비어 있는지 검사
function chk_editor_js($id, $is_dhtml_editor=true)
{
    if ($is_dhtml_editor) {
        return "if (!{$id}_editor_data || jQuery.inArray({$id}_editor_data.toLowerCase(), ['&nbsp;','<p>&nbsp;</p>','<p><br></p>','<p></p>','<br>']) != -1) { "
            ."alert(\"내용을 입력해 주십시오.\"); "
            ."if (typeof oEditors !== 'undefined' && oEditors.getById && oEditors.getById['{$id}']) { "
            ."oEditors.getById['{$id}'].exec('FOCUS'); "
            ."} else if (document.getElementById('{$id}')) { "
            ."document.getElementById('{$id}').focus(); "
            ."} "
            ."return false; }\n";
    } else {
        return "if (!{$id}_editor.value) { alert(\"내용을 입력해 주십시오.\"); {$id}_editor.focus(); return false; }\n";
    }
}

/*
https://github.com/timostamm/NonceUtil-PHP
*/

if (!defined('FT_NONCE_UNIQUE_KEY'))
    define('FT_NONCE_UNIQUE_KEY', sha1($_SERVER['SERVER_SOFTWARE'].G5_MYSQL_USER.session_id().G5_TABLE_PREFIX));

if (!defined('FT_NONCE_SESSION_KEY'))
    define('FT_NONCE_SESSION_KEY', substr(md5(FT_NONCE_UNIQUE_KEY), 5));

if (!defined('FT_NONCE_DURATION'))
    define('FT_NONCE_DURATION', 60 * 60); // 1시간

if (!defined('FT_NONCE_KEY'))
    define('FT_NONCE_KEY', '_nonce');

// This method creates a key / value pair for a url string
if (!function_exists('ft_nonce_create_query_string')) {
    function ft_nonce_create_query_string($action = '', $user = ''){
        return FT_NONCE_KEY."=".ft_nonce_create($action, $user);
    }
}

if (!function_exists('ft_get_secret_key')) {
    function ft_get_secret_key($secret){
        return md5(FT_NONCE_UNIQUE_KEY.$secret);
    }
}

// This method creates a nonce. It should be called by one of the previous two functions.
if (!function_exists('ft_nonce_create')) {
    function ft_nonce_create($action = '', $user = '', $timeoutSeconds = FT_NONCE_DURATION){

        $secret = ft_get_secret_key($action.$user);

        set_session('token_'.FT_NONCE_SESSION_KEY, $secret);

        $salt = ft_nonce_generate_hash();
        $time = time();
        $maxTime = $time + $timeoutSeconds;
        $nonce = $salt . "|" . $maxTime . "|" . sha1($salt . $secret . $maxTime);

        return $nonce;
    }
}

// This method validates a nonce
if (!function_exists('ft_nonce_is_valid')) {
    function ft_nonce_is_valid($nonce, $action = '', $user = ''){

        $secret = ft_get_secret_key($action.$user);
        $token = get_session('token_'.FT_NONCE_SESSION_KEY);

        if ($secret != $token) {
            return false;
        }

        if (!is_string($nonce)) {
            return false;
        }

        $a = explode('|', $nonce);
        if (count($a) != 3) {
            return false;
        }

        $salt = $a[0];
        $maxTime = intval($a[1]);
        $hash = $a[2];
        $back = sha1($salt . $secret . $maxTime);

        if ($back != $hash) {
            return false;
        }

        if (time() > $maxTime) {
            return false;
        }

        return true;
    }
}

// This method generates the nonce timestamp
if (!function_exists('ft_nonce_generate_hash')) {
    function ft_nonce_generate_hash(){
        $length = 10;
        $chars = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        $ll = strlen($chars) - 1;
        $o = '';

        while (strlen($o) < $length) {
            $o .= $chars[rand(0, $ll)];
        }

        return $o;
    }
}