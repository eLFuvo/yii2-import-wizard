<?php

use elfuvo\import\services\ImportServiceInterface;
use elfuvo\import\tests\app\models\Review;
use yii\di\Instance;

/**
 *
 */
class MappedUploadImportFileCest
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
    public function importByStaticMapTest(FunctionalTester $I)
    {
        // clean up queue before pushing import job
        $I->runShellCommand('/app/tests/app/yii queue/clear --interactive 0');
        $this->service->getResult()->resetBatch();

        $I->amOnPage('/default/upload-file-import-map');
        // must be ok
        // file is stored in 'tests/_data'
        $I->attachFile('#importFile', 'reviews.xlsx');
        $I->click('.import-form button[type="submit"]');

        $I->runShellCommand('/app/tests/app/yii queue/run');
        sleep(2);
        // load result
        $this->service->getResult()->getLastBatch();
        $I->assertCount(1, $this->service->getResult()->getErrors());
        $I->assertEquals($this->service->getResult()->getProgressDone(), 4);
        $review = Review::findOne(['title' => 'Отзыв']);
        $I->assertInstanceOf(Review::class,$review);
        $I->assertEquals($review->rating, '5');
        $I->assertEquals($review->language, 'ru');
        $I->assertEquals($review->b24StationId, '138');
    }
}
