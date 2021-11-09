<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import;

use elfuvo\import\exception\MemoryLimitException;
use elfuvo\import\adapter\AdapterImportInterface;
use elfuvo\import\services\ImportServiceInterface;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\queue\JobInterface;
use yii\queue\RetryableJobInterface;

/**
 * Class ImportJob
 * @package elfuvo\import
 */
class ImportJob extends BaseObject implements JobInterface, RetryableJobInterface
{
    const MAX_ATTEMPTS = 5;

    /**
     * @var AdapterImportInterface
     */
    public $adapter;

    /**
     * @var \elfuvo\import\models\MapAttribute[]
     */
    public $mapAttribute;

    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var array
     */
    public $modelAttributes = [];

    /**
     * @var \elfuvo\import\services\ImportServiceInterface
     */
    protected $service;

    /**
     * ImportJob constructor.
     * @param ImportServiceInterface $service
     * @param array $config
     */
    public function __construct(ImportServiceInterface $service, array $config = [])
    {
        $this->service = $service;

        parent::__construct($config);
    }

    /**
     *
     */
    public function init()
    {
        if (!$this->adapter) {
            throw new InvalidArgumentException('Adapter must be set');
        }
        if (!$this->mapAttribute) {
            throw new InvalidArgumentException('Map attributes must be set');
        }
        if (!$this->modelClass) {
            throw new InvalidArgumentException('Model must be set');
        }

        parent::init();
    }

    /**
     * @param \yii\queue\Queue $queue
     * @return bool
     * @throws MemoryLimitException
     */
    public function execute($queue)
    {
        // ActiveRecord::save cause memory leak
        // @see https://github.com/yiisoft/yii2/issues/9679#issuecomment-140364996
        Yii::getLogger()->flushInterval = 1;

        $memoryLimit = $this->getMemoryLimit();

        /** @var Model $model */
        $model = Yii::createObject($this->modelClass);
        $model->load($this->modelAttributes, '');
        $this->service
            ->setModel($model)
            ->setMap($this->mapAttribute)
            ->setAdapter($this->adapter);

        while ($this->service->importBatch()) {
            $memory = memory_get_usage();
            if ($memory > $memoryLimit) {
                throw new MemoryLimitException('Memory limit exceed');
            }
        }

        return true;
    }

    /**
     * @return int time to reserve in seconds
     */
    public function getTtr()
    {
        return 600; // 10 min
    }

    /**
     * @param int $attempt number
     * @param \Exception|\Throwable $error from last execute of the job
     * @return bool
     */
    public function canRetry($attempt, $error)
    {
        if ($error instanceof MemoryLimitException &&
            $attempt < self::MAX_ATTEMPTS) {
            return true;
        }

        return false;
    }

    /**
     * @return float|int|string
     */
    protected function getMemoryLimit()
    {
        $memoryLimit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
            switch ($matches[2]) {
                case 'M':
                    $memoryLimit = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
                    break;
                case 'K':
                    $memoryLimit = $matches[1] * 1024; // nnnK -> nnn KB
                    break;
                case 'G':
                    $memoryLimit = $matches[1] * 1024 * 1024 * 1024; // nnnG -> nnn GB
                    break;
                default:
                    $memoryLimit = $matches[1] * 1024 * 1024;
            }
        }
        $memoryLimit = (int)preg_replace('#([^\d]+)#', '', $memoryLimit);
        $memoryLimit -= 5 * 1024 * 1024;
        if ($memoryLimit <= 0) {
            $memoryLimit = 8 * 1024 * 1024;
        }
        return $memoryLimit;
    }
}
