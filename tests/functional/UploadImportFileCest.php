<?php

use elfuvo\import\models\MapAttribute;
use elfuvo\import\services\BracketValueCaster;
use elfuvo\import\services\ImportServiceInterface;
use elfuvo\import\tests\app\models\Review;
use yii\di\Instance;

/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:37
 */
class UploadImportFileCest
{
    /**
     * @var \elfuvo\import\services\ImportServiceInterface
     */
    protected $service;

    /**
     * @param \FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $this->service = Instance::ensure(ImportServiceInterface::class, ImportServiceInterface::class);
        $this->service->setModel(new Review());
        Review::deleteAll();
    }

    // tests

    /**
     * @param FunctionalTester $I
     */
    public function formValidationTest(FunctionalTester $I)
    {
        $I->amOnPage('/default/upload-file-import');
        $I->seeElement('#importFile');
        $I->click('.import-form button[type="submit"]');
        // no file error
        $I->seeElement('.form-group.has-error');
        // wrong file error
        $I->attachFile('#importFile', 'reviews.txt');
        $I->click('.import-form button[type="submit"]');
        $I->seeElement('.form-group.has-error');
        // must be ok
        // file is stored in 'tests/_data'
        $I->attachFile('#importFile', 'reviews.xlsx');
        $I->click('.import-form button[type="submit"]');
        // next step - setup import map
        $I->seeElement('.attribute');
    }

    /**
     * @param FunctionalTester $I
     */
    public function setupImportTest(FunctionalTester $I)
    {
        // clean up queue before pushing import job
        $I->runShellCommand('/app/tests/app/yii queue/clear --interactive 0');
        $this->service->getResult()->resetBatch();

        $I->amOnPage('/default/setup-import');
        $I->seeElement('.attribute');
        // check custom casters is presented in setup form
        $I->seeElement('.type option[value="' . BracketValueCaster::class . '"]');

        // reviews.xlsx
        // A            | B     | C      | D    | E      | F
        // b24StationId | title | author | text | rating | date of publication

        // configure form and send it
        /**
         * @see Review
         */
        $I->selectOption('.attribute[data-id="A"] select', ['value' => 'b24StationId']);
        $I->selectOption('.type[data-id="A"] select', ['value' => BracketValueCaster::class]);
        $I->selectOption('.attribute[data-id="B"] select', ['value' => 'title']);
        $I->checkOption('.identity[data-id="B"]');
        $I->selectOption('.attribute[data-id="C"] select', ['value' => 'author']);
        $I->selectOption('.attribute[data-id="D"] select', ['value' => 'text']);

        $I->selectOption('.attribute[data-id="E"] select', ['value' => 'rating']);
        $I->selectOption('.type[data-id="E"] select', ['value' => MapAttribute::TYPE_FLOAT]);

        $I->selectOption('.attribute[data-id="F"] select', ['value' => 'publishAt']);
        $I->selectOption('.type[data-id="F"] select', ['value' => MapAttribute::TYPE_DATETIME]);

        $I->click('.setup-import-form button[type="submit"]');
        $I->dontSeeElement('.form-group.has-error');
    }

    /**
     * @param FunctionalTester $I
     */
    public function successSavedTest(FunctionalTester $I)
    {
        $I->runShellCommand('/app/tests/app/yii queue/run');
        sleep(2);
        // load results
        $this->service->getResult()->getLastBatch();
        $I->assertCount(1, $this->service->getResult()->getErrors());
        $I->assertEquals($this->service->getResult()->getProgressDone(), 4);
        $review = Review::findOne(['title' => 'Отзыв']);
        $I->assertInstanceOf(Review::class, $review);
        $I->assertEquals($review->rating, '5');
        $I->assertEquals($review->language, 'ru');
        $I->assertEquals($review->b24StationId, '138');
    }
}
