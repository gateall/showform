<?php
$log = file_get_contents('C:\Users\USER\.gemini\antigravity-ide\brain\d57da381-b48e-4a56-a697-c5a6a01967b4\.system_generated\logs\transcript.jsonl');
$lines = explode("\n", $log);
foreach($lines as $line) {
    $data = json_decode($line, true);
    if($data && $data['type'] == 'VIEW_FILE' && strpos($data['content'], 'knusu/head.php') !== false) {
        $content = $data['content'];
        $content = preg_replace('/^(\d+:\s)/m', '', $content);
        file_put_contents('orig_head.txt', $content);
        echo "Extracted.\n";
        break;
    }
}
