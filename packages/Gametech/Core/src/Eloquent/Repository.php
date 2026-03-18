<?php

namespace Gametech\Core\Eloquent;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Prettus\Repository\Contracts\CacheableInterface;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Repository\Traits\CacheableRepository;

/**
 * Base Repository (hardened)
 *
 * - บังคับให้ทุกรีโพชี้ model() ไปยัง Eloquent Model เท่านั้น
 * - จับและรายงาน error แบบอ่านง่าย เมื่อ model() คืนคลาสผิด/หาไม่เจอ
 */
abstract class Repository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;

    /**
     * Prettus จะเรียก makeModel ระหว่าง resolve รีโพ
     * เรา override เพื่อ:
     *  - new คลาสโดยตรง (เลี่ยง container ยัด attributes แปลก ๆ)
     *  - ตรวจว่าเป็น EloquentModel จริง
     *  - โยนข้อผิดพลาดแบบชัดเจน
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function makeModel()
    {
        $class = $this->model();

        if (!is_string($class) || $class === '') {
            \Log::error('Repository::makeModel invalid model() return', [
                'repository' => static::class,
                'model'      => $class,
            ]);
            throw new RepositoryException('model() ต้องคืนชื่อคลาสเป็นสตริงที่ไม่ว่าง');
        }

        if (!class_exists($class)) {
            \Log::error('Repository::makeModel class not found', [
                'repository' => static::class,
                'model'      => $class,
            ]);
            throw new RepositoryException("ไม่พบคลาสโมเดล: {$class} (ถูกเรียกจาก " . static::class . ')');
        }

        // new ตรง ๆ เพื่อกัน container ใส่ args แปลก ๆ (เช่น request()->all())
        $model = new $class();

        if (!$model instanceof EloquentModel) {
            \Log::error('Repository::makeModel not eloquent', [
                'repository' => static::class,
                'model'      => $class,
                'type'       => is_object($model) ? get_class($model) : gettype($model),
            ]);
            throw new RepositoryException("{$class} ต้องเป็น instance ของ Illuminate\\Database\\Eloquent\\Model (ถูกเรียกจาก " . static::class . ')');
        }

        $this->model = $model;

        return $this->model;
    }

    /**
     * Find data by field and value
     *
     * @param  string       $field
     * @param  string|mixed $value
     * @param  array        $columns
     * @return mixed
     */
    public function findOneByField($field, $value = null, $columns = ['*'])
    {
        $model = $this->findByField($field, $value, $columns);
        return $model->first();
    }

    /**
     * Find data by conditions
     *
     * @param  array $where
     * @param  array $columns
     * @return mixed
     */
    public function findOneWhere(array $where, $columns = ['*'])
    {
        $model = $this->findWhere($where, $columns);
        return $model->first();
    }

    /**
     * Find by id
     *
     * @param  int|string $id
     * @param  array      $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->find($id, $columns);

        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * Find by id or fail
     *
     * @param  int|string $id
     * @param  array      $columns
     * @return mixed
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();

        $model = $this->model->findOrFail($id, $columns);

        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * Count results of repository
     *
     * @param  array  $where
     * @param  string $columns
     * @return int
     */
    public function count(array $where = [], $columns = '*')
    {
        $this->applyCriteria();
        $this->applyScope();

        if ($where) {
            $this->applyConditions($where);
        }

        $result = $this->model->count($columns);

        $this->resetModel();
        $this->resetScope();

        return $result;
    }

    /**
     * Sum column
     *
     * @param  string $columns
     * @return mixed
     */
    public function sum($columns)
    {
        $this->applyCriteria();
        $this->applyScope();

        $sum = $this->model->sum($columns);

        $this->resetModel();

        return $sum;
    }

    /**
     * Average column
     *
     * @param  string $columns
     * @return mixed
     */
    public function avg($columns)
    {
        $this->applyCriteria();
        $this->applyScope();

        $avg = $this->model->avg($columns);

        $this->resetModel();

        return $avg;
    }

    /**
     * Get current Eloquent model instance
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
