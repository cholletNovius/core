<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */
$uniqid_close = uniqid('close_');

Nos\I18n::current_dictionary($i18n_files);

if (!$item->is_new()) {
    ?>
    <div id="<?= $uniqid_close ?>" style="display:none;">
        <p><?= __('item deleted') ?></p>
        <p>&nbsp;</p>
        <p><button class="primary" data-icon="close" onclick="$(this).nosTabs('close');"><?= __('Close tab') ?></button></p>
    </div>
    <?php
}
?>
<script type="text/javascript">
    require(
        ['jquery-nos-update-tab-crud'],
        function ($) {
            $(function () {
                var $container = $('#<?= isset($container_id) ? $container_id : $fieldset->form()->get_attribute('id') ?>').nosUpdateTabCrud({
                            tabParams: <?= \Format::forge()->to_json($crud['tab_params']) ?>,
                            isNew: <?= \Format::forge()->to_json($item->is_new()) ?>,
                            model: <?= \Format::forge()->to_json($crud['model']) ?>,
                            itemId: <?= (int) $item->{$crud['pk']} ?>,
                            closeEle: '#<?= $uniqid_close ?>',
                            texts: {
                                titleClose: <?= Format::forge()->to_json(__('Bye bye')) ?>
                            }
                        }),
                    context = <?= \Format::forge()->to_json(isset($crud['context']) ? $crud['context'] : false) ?>;
                if (context) {
                    $container.closest('.nos-dispatcher').data('nosContext', context);
                }
            });
        });
</script>
