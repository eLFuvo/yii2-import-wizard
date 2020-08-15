<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:33
 */

/* @var $this \yii\web\View */

/* @var $content string */

?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    $this->registerCsrfMetaTags() ?>
    <title>Import wizard</title>
    <?php
    $this->head() ?>
</head>
<body>
<?php
$this->beginBody() ?>

<div class="wrap">
    <?= $content; ?>
</div>

<?php
$this->endBody() ?>
</body>
</html>
<?php
$this->endPage() ?>
