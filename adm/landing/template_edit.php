<?php
$sub_menu = "900100"; // 랜딩관리 서브메뉴
include_once('./_common.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) alert('잘못된 접근입니다. 랜딩페이지 ID가 필요합니다.', './landing_list.php');

$table = G5_TABLE_PREFIX . 'landing_page';

// 커스텀 빌더용 컬럼이 없으면 자동 추가
$sql_check = " show columns from {$table} like 'custom_html' ";
if (!sql_fetch($sql_check)) {
    sql_query(" ALTER TABLE {$table} ADD `custom_html` LONGTEXT NULL ", false);
    sql_query(" ALTER TABLE {$table} ADD `custom_css` LONGTEXT NULL ", false);
}

$row = sql_fetch(" select * from {$table} where id = '{$id}' ");
if (!$row) alert('존재하지 않는 랜딩페이지입니다.', './landing_list.php');

$g5['title'] = '랜딩페이지 비주얼 에디터 - ' . get_text($row['subject']);

// GrapesJS 에디터 화면에서는 그누보드 기본 관리자 헤더/푸터를 제외하고 전체화면으로 렌더링합니다.
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo $g5['title']; ?></title>
    <!-- GrapesJS Core -->
    <link href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" rel="stylesheet">
    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-preset-webpage"></script>
    <script src="https://unpkg.com/grapesjs-blocks-basic"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: 'Malgun Gothic', sans-serif; }
        #gjs { height: 100vh; overflow: hidden; }
        
        /* 커스텀 상단 툴바 */
        .panel__top { padding: 0; width: 100%; display: flex; position: initial; justify-content: space-between; align-items: center; background: #1e293b; color: #fff; height: 50px; }
        .panel__top-left { display: flex; align-items: center; padding-left: 15px; font-weight: bold; }
        .panel__top-center { display: flex; justify-content: center; align-items: center; flex: 1; }
        .panel__top-right { display: flex; padding-right: 15px; gap: 10px; }
        
        /* 버튼 스타일 */
        .btn-custom { background: #3b82f6; color: white; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold; }
        .btn-custom:hover { background: #2563eb; }
        .btn-ai { background: #8b5cf6; }
        .btn-ai:hover { background: #7c3aed; }
        .btn-exit { background: #ef4444; }
        .btn-exit:hover { background: #dc2626; }
        
        .gjs-cv-canvas { top: 0; width: 100%; height: 100%; }
        
        /* AI 로딩 모달 */
        #ai_loading { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center; color:#fff; font-size:20px; font-weight:bold; }
    </style>
</head>
<body>

<div id="ai_loading">🤖 AI가 카피를 최적화하고 있습니다... 잠시만 기다려주세요.</div>

<div class="panel__top">
    <div class="panel__top-left">
        ✨ ShowForm 비주얼 에디터
    </div>
    <div class="panel__top-center" id="panel-devices">
        <!-- 모바일/PC 토글 (GrapesJS 패널 연동) -->
    </div>
    <div class="panel__top-right">
        <button class="btn-custom btn-ai" id="btn-ai-copy" title="텍스트 블록 선택 후 누르면 AI가 문구를 개선해 줍니다.">🤖 AI 카피라이팅</button>
        <button class="btn-custom" id="btn-save">💾 저장하기</button>
        <button class="btn-custom btn-exit" onclick="location.href='./landing_form.php?id=<?php echo $id; ?>'">나가기</button>
    </div>
</div>

<!-- GrapesJS 컨테이너 -->
<div id="gjs">
    <?php 
        // 기존에 저장된 커스텀 HTML이 있으면 로드, 없으면 빈 캔버스
        if ($row['custom_html']) {
            echo $row['custom_html'];
            if ($row['custom_css']) {
                echo "<style>{$row['custom_css']}</style>";
            }
        } else {
            // 초기 템플릿 안내문
            echo '<div style="padding: 50px; text-align: center; font-family: sans-serif;">
                    <h1 style="color:#1d4ed8;">드래그 앤 드롭으로 자유롭게 꾸며보세요!</h1>
                    <p>우측 블록 패널에서 텍스트, 이미지, 버튼 등을 끌어다 놓으세요.</p>
                  </div>';
        }
    ?>
</div>

<script>
    const editor = grapesjs.init({
        container: '#gjs',
        height: 'calc(100vh - 50px)',
        fromElement: true,
        showOffsets: true,
        noticeOnUnload: false,
        storageManager: false, // 커스텀 Ajax 저장 사용
        panels: { defaults: [] },
        plugins: ['gjs-blocks-basic', 'gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-preset-webpage': {
                blocksBasicOpts: { flexGrid: true }
            }
        },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Mobile', width: '320px', widthMedia: '480px' }
            ]
        }
    });

    // Device Manager (PC / Mobile 토글)
    editor.Panels.addPanel({
        id: 'panel-devices',
        el: '#panel-devices',
        buttons: [
            {
                id: 'device-desktop',
                label: '💻 PC',
                command: 'set-device-desktop',
                active: true,
                togglable: false,
            },
            {
                id: 'device-mobile',
                label: '📱 모바일',
                command: 'set-device-mobile',
                togglable: false,
            }
        ]
    });
    editor.Commands.add('set-device-desktop', { run: ed => ed.setDevice('Desktop') });
    editor.Commands.add('set-device-mobile', { run: ed => ed.setDevice('Mobile') });

    // 우측 기본 패널 복원
    editor.Panels.addPanel({
        id: 'basic-actions',
        el: '.panel__top-right',
        buttons: []
    });

    // 저장 로직 (Ajax)
    $('#btn-save').on('click', function() {
        const html = editor.getHtml();
        const css = editor.getCss();
        
        $(this).text('저장 중...').prop('disabled', true);
        
        $.post('./template_save.php', {
            id: <?php echo $id; ?>,
            html: html,
            css: css
        }, function(res) {
            $('#btn-save').text('💾 저장하기').prop('disabled', false);
            if(res.success) {
                alert('레이아웃이 성공적으로 저장되었습니다!');
            } else {
                alert('저장 실패: ' + res.error);
            }
        }, 'json').fail(function(){
            $('#btn-save').text('💾 저장하기').prop('disabled', false);
            alert('서버 통신 오류가 발생했습니다.');
        });
    });

    // AI 카피라이팅 연동 로직
    $('#btn-ai-copy').on('click', function() {
        const selected = editor.getSelected();
        
        if (!selected) {
            alert('수정할 텍스트 컴포넌트를 먼저 캔버스에서 클릭하여 선택해주세요.');
            return;
        }

        // 선택된 컴포넌트가 텍스트 타입인지 확인
        if (selected.get('type') !== 'text' && selected.get('type') !== 'textnode' && !selected.is('text')) {
            alert('텍스트가 포함된 요소를 선택해주세요.');
            return;
        }

        // 현재 텍스트 가져오기 (태그 제거)
        const currentText = selected.components().models[0] ? selected.components().models[0].get('content') : selected.get('content');
        const cleanText = $('<div>').html(currentText).text().trim();
        
        if (!cleanText) {
            alert('선택한 요소에 텍스트가 없습니다.');
            return;
        }

        const tone = prompt('어떤 느낌으로 변경할까요?\n(예: 고급스럽게, 시급성을 강조해서, 친근하게)', '고급스럽고 신뢰감 있게');
        if (!tone) return;

        $('#ai_loading').css('display', 'flex');

        $.post('./ai_copywriter.php', {
            original_text: cleanText,
            tone: tone
        }, function(res) {
            $('#ai_loading').hide();
            if(res.success && res.improved_text) {
                // 에디터 컴포넌트 내용 교체
                selected.components(res.improved_text);
            } else {
                alert('AI 텍스트 생성 실패: ' + (res.error || '알 수 없는 오류'));
            }
        }, 'json').fail(function(){
            $('#ai_loading').hide();
            alert('AI 서버 통신 오류가 발생했습니다.');
        });
    });

</script>
</body>
</html>
