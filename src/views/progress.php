<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

/** @var yii\web\View $this */
/** @var \elfuvo\import\result\ResultImportInterface $result */

?>
<?php
if ($result->getProgressTotal() && $result->getProgressDone() < $result->getProgressTotal()):
    $percentDone = $result->getProgressDone() > 0 ?
        round($result->getProgressDone() / $result->getProgressTotal() * 100) : 1;
    ?>
    <div class="card-content">
        <div class="well">
            <p><?= Yii::t('import-wizard', 'Data is importing'); ?>...</p>
            <p><?= Yii::t('import-wizard', 'Imported'); ?>:
                <?= $result->getProgressDone(); ?> / <?= $result->getProgressTotal(); ?></p>
            <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="<?= $percentDone; ?>"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     style="width: <?= $percentDone; ?>%">
                    <span class="sr-only"></span>
                </div>
            </div>
        </div>
    </div>
<?php
elseif ($result->getProgressTotal() && $result->getProgressDone() == $result->getProgressTotal()): ?>
    <div class="card-content import-done">
        <div class="well">
            <p><?= Yii::t('import-wizard', 'Data imported'); ?></p>
            <p><?= Yii::t('import-wizard', 'Import statistics'); ?>: <br/>
                <span><?= Yii::t('import-wizard', 'Items added'); ?>:
                    <?= $result->getCounter($result::ADD_COUNTER); ?></span><br/>
                <span><?= Yii::t('import-wizard', 'Items updated'); ?>:
                    <?= $result->getCounter($result::UPDATE_COUNTER); ?></span><br/>
                <span><?= Yii::t('import-wizard', 'Items deleted'); ?>:
                    <?= $result->getCounter($result::DELETE_COUNTER); ?></span><br/>
                <span><?= Yii::t('import-wizard', 'Ignored lines'); ?>:
                    <?= $result->getCounter($result::SKIP_COUNTER); ?></span><br/>
            </p>
        </div>
        <?php
        if ($result->hasErrors()): ?>
            <div class="alert alert-danger">
                <p>
                    <?= Yii::t('import-wizard', 'Errors'); ?>:
                </p>
                <p>
                    <?= implode('<br />', $result->getErrors()); ?>
                </p>
            </div>
        <?php
        endif; ?>
    </div>
<?php
endif; ?>
