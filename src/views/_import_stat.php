<?php
/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 07.05.19
 * Time: 16:14
 */

/** @var yii\web\View $this */
/** @var \elfuvo\import\result\ResultImportInterface $result */

?>
<?php if ($result->getProgressTotal() && $result->getProgressDone() < $result->getProgressTotal()):
    $percentDone = $result->getProgressDone() > 0 ?
        round($result->getProgressDone() / $result->getProgressTotal() * 100) : 1;
    ?>
    <div class="card-content">
        <div class="well">
            <p>Данные импортируются...</p>
            <p>Импортировано: <?= $result->getProgressDone(); ?> из <?= $result->getProgressTotal(); ?></p>
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
    $this->registerJs('$(window).trigger("import.stat.reload")');
    ?>
<?php elseif ($result->getProgressTotal() && $result->getProgressDone() == $result->getProgressTotal()): ?>
    <div class="card-content import-done">
        <div class="well">
            <p>Данные импортированы</p>
            <p>Статистика импорта: <br/>
                <span>Добавлено элементов: <?= $result->getCounter($result::ADD_COUNTER); ?></span><br/>
                <span>Обнолено элементов: <?= $result->getCounter($result::UPDATE_COUNTER); ?></span><br/>
                <span>Удалено элементов: <?= $result->getCounter($result::DELETE_COUNTER); ?></span><br/>
                <span>Проигнорировано строк: <?= $result->getCounter($result::SKIP_COUNTER); ?></span><br/>
            </p>
        </div>
        <?php if ($result->hasErrors()): ?>
            <div class="alert alert-danger">
                <p>
                    Ошибки:
                </p>
                <p>
                    <?= implode('<br />', $result->getErrors()); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
