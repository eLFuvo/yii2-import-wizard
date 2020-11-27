<?php

use elfuvo\import\app\models\Review;
use elfuvo\import\MapAttribute;
use elfuvo\import\result\FileContinuesResultImport;

/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:37
 */
class UploadImportFileCest
{
    public function _before(FunctionalTester $I)
    {
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

        $I->amOnPage('/default/setup-import');
        $I->seeElement('.attribute');

        // reviews.xlsx
        // A            | B     | C      | D    | E      | F
        // b24StationId | title | author | text | rating | date of publication

        // configure form and send it
        $I->selectOption('.attribute[data-id="A"] select', ['value' => MapAttribute::IGNORE_COLUMN]);
        /**
         * @see Review
         */
        $I->selectOption('.attribute[data-id="B"] select', ['value' => 'title']);
        $I->selectOption('.attribute[data-id="C"] select', ['value' => 'author']);
        $I->selectOption('.attribute[data-id="D"] select', ['value' => 'text']);

        $I->selectOption('.attribute[data-id="E"] select', ['value' => 'rating']);
        $I->selectOption('.type[data-id="A"] select', ['value' => MapAttribute::TYPE_FLOAT]);

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
        sleep(5);
        $result = new FileContinuesResultImport();
        $result->pointerPath = dirname(__DIR__) . '/app/runtime/import';
        $result->setKey('Review');
        $result->getLastBatch();

        $I->assertEmpty($result->getErrors());
        $I->assertEquals($result->getProgressDone(), 2);
    }
}
