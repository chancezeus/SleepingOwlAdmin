<?php

namespace SleepingOwl\Admin\Form\Element;

use Closure;
use Storage;
use Illuminate\Routing\Router;
use Illuminate\Http\UploadedFile;
use KodiComponents\Support\Upload;
use SleepingOwl\Admin\Contracts\WithRoutesInterface;

class File extends NamedFormElement implements WithRoutesInterface
{
    /**
     * @var string
     */
    protected static $route = 'file';

    /**
     * @var \Closure
     */
    protected $saveCallback;

    /**
     * @param Router $router
     */
    public static function registerRoutes(Router $router)
    {
        $routeName = 'admin.form.element.'.static::$route;

        if (! $router->has($routeName)) {
            $router->post('{adminModel}/'.static::$route.'/{field}/{id?}', [
                'as'   => $routeName,
                'uses' => 'SleepingOwl\Admin\Http\Controllers\UploadController@fromField',
            ]);
        }
    }

    /**
     * @var string
     */
    protected $driver = 'file';

    /**
     * @var array
     */
    protected $driverOptions = [];

    /**
     * @var string
     */
    protected $uploadDisk;

    /**
     * @var Closure
     */
    protected $uploadPath;

    /**
     * @var Closure
     */
    protected $uploadFileName;

    /**
     * @var array
     */
    protected $uploadSettings = [];

    /**
     * @var array
     */
    protected $uploadValidationRules = ['required', 'file'];

    /**
     * @var string
     */
    protected $view = 'form.element.file';

    /**
     * @return array
     */
    public function getUploadValidationMessages()
    {
        $messages = [];
        foreach ($this->validationMessages as $rule => $message) {
            $messages["file.{$rule}"] = $message;
        }

        return $messages;
    }

    /**
     * @return array
     */
    public function getUploadValidationLabels()
    {
        return ['file' => $this->getLabel()];
    }

    /**
     * @param $driver
     * @param array $driverOptions
     * @return $this
     */
    public function setDriver($driver, $driverOptions = [])
    {
        $this->driver = $driver;
        $this->driverOptions = $driverOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getDriver()
    {
        return ['driver' => $this->driver, 'driverOptions' => $this->driverOptions];
    }

    /**
     * @return array
     */
    public function getUploadValidationRules()
    {
        return ['file' => array_unique($this->uploadValidationRules)];
    }

    /**
     * @return string
     */
    public function getUploadDisk()
    {
        if (!$this->uploadDisk) {
            return $this->defaultUploadDisk();
        }

        return $this->uploadDisk;
    }

    /**
     * @param string $uploadDisk
     *
     * @return $this
     */
    public function setUploadDisk($uploadDisk)
    {
        $this->uploadDisk = $uploadDisk;

        return $this;
    }

    /**
     * @param UploadedFile $file
     *
     * @return mixed
     */
    public function getUploadPath(UploadedFile $file)
    {
        if (! is_callable($this->uploadPath)) {
            return $this->defaultUploadPath($file);
        }

        return call_user_func($this->uploadPath, $file);
    }

    /**
     * @param Closure $uploadPath
     *
     * @return $this
     */
    public function setUploadPath(Closure $uploadPath)
    {
        $this->uploadPath = $uploadPath;

        return $this;
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    public function getUploadFileName(UploadedFile $file)
    {
        if (! is_callable($this->uploadFileName)) {
            return $this->defaultUploadFilename($file);
        }

        return call_user_func($this->uploadFileName, $file);
    }

    /**
     * @param Closure $uploadFileName
     *
     * @return $this
     */
    public function setUploadFileName(Closure $uploadFileName)
    {
        $this->uploadFileName = $uploadFileName;

        return $this;
    }

    /**
     * @return array
     */
    public function getUploadSettings()
    {
        if (empty($this->uploadSettings) && in_array(Upload::class, class_uses($this->getModel()))) {
            return (array) array_get($this->getModel()->getUploadSettings(), $this->getPath());
        }

        return $this->uploadSettings;
    }

    /**
     * @param array $imageSettings
     *
     * @return $this
     */
    public function setUploadSettings(array $imageSettings)
    {
        $this->uploadSettings = $imageSettings;

        return $this;
    }

    /**
     * @param string $rule
     * @param string|null $message
     *
     * @return $this
     */
    public function addValidationRule($rule, $message = null)
    {
        $uploadRules = ['file', 'image', 'mime', 'size', 'dimensions', 'max', 'min', 'between'];

        foreach ($uploadRules as $uploadRule) {
            if (strpos($rule, $uploadRule) !== false) {
                $this->uploadValidationRules[] = $rule;

                if (is_null($message)) {
                    return $this;
                }

                return $this->addValidationMessage($rule, $message);
            }
        }

        return parent::addValidationRule($rule, $message);
    }

    /**
     * @param int $size Max size in kilobytes
     *
     * @return $this
     */
    public function maxSize($size)
    {
        $this->addValidationRule('max:'.(int) $size);

        return $this;
    }

    /**
     * @param int $size Max size in kilobytes
     *
     * @return $this
     */
    public function minSize($size)
    {
        $this->addValidationRule('min:'.(int) $size);

        return $this;
    }

    /**
     * Set save file callback.
     * @param \Closure $callable
     */
    public function setSaveCallback(\Closure $callable)
    {
        $this->saveCallback = $callable;

        return $this;
    }

    /**
     * Return save callback.
     * @return \Closure
     */
    public function getSaveCallback()
    {
        return $this->saveCallback;
    }

    /**
     * @param UploadedFile $file
     * @param string $disk
     * @param string $path
     * @param string $filename
     * @param array $settings
     * @return \Closure|array
     */
    public function saveFile(UploadedFile $file, $disk, $path, $filename, array $settings)
    {
        if ($this->getSaveCallback()) {
            $callable = $this->getSaveCallback();

            return call_user_func($callable, [$file, $disk, $path, $filename, $settings]);
        }

        $storage = Storage::disk($disk);

        $storage->putFileAs($path, $file, $filename);

        //TODO: Make sense take s3, rackspace or some cloud storage url
        $value = $path.'/'.$filename;

        return ['path' => $storage->url($value), 'value' => $value];
    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     */
    public function customValidation(\Illuminate\Validation\Validator $validator)
    {
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    public function defaultUploadFilename(UploadedFile $file)
    {
        return md5(time().$file->getClientOriginalName()).'.'.$file->getClientOriginalExtension();
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    public function defaultUploadPath(UploadedFile $file)
    {
        return config('sleeping_owl.filesUploadDirectory', 'files/uploads');
    }

    /**
     * @return string
     */
    public function defaultUploadDisk()
    {
        return config('sleeping_owl.filesUploadDisk', 'public');
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'fileUrl' => is_string($value = $this->getValueFromModel()) ? Storage::disk($this->getUploadDisk())->url($value) : null
        ]);
    }
}
