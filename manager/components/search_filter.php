<?php
if (!defined('_GNUBOARD_')) exit;

// $fields = array(array('type'=>'text|select','name'=>'sfl','label'=>'검색어','options'=>array('value'=>'label')))
function mgr_search_filter($action_url, $fields, $opts = array())
{
    $submit_label = isset($opts['submit_label']) ? $opts['submit_label'] : '검색';
    ob_start();
?>
<form method="get" action="<?php echo $action_url ?>" class="mgr-card mgr-search-filter">
    <?php foreach ($fields as $field): ?>
        <?php if ($field['type'] === 'select'): ?>
        <div class="mgr-filter-field">
            <?php if (!empty($field['label'])): ?><label><?php echo get_text($field['label']) ?></label><?php endif; ?>
            <select name="<?php echo $field['name'] ?>" class="mgr-input">
                <?php foreach ($field['options'] as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value) ?>" <?php echo (isset($field['value']) && (string)$field['value'] === (string)$value) ? 'selected' : ''; ?>><?php echo get_text($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <div class="mgr-filter-field">
            <?php if (!empty($field['label'])): ?><label><?php echo get_text($field['label']) ?></label><?php endif; ?>
            <input type="text" name="<?php echo $field['name'] ?>" value="<?php echo htmlspecialchars(isset($field['value']) ? $field['value'] : '') ?>" class="mgr-input" placeholder="<?php echo isset($field['placeholder']) ? get_text($field['placeholder']) : '' ?>">
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <button type="submit" class="mgr-btn mgr-btn-primary"><?php echo get_text($submit_label) ?></button>
</form>
<?php
    return ob_get_clean();
}
