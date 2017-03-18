<?php

namespace SleepingOwl\Admin\Form\Element;

use Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class Image extends File
{
    /**
     * @var string
     */
    protected static $route = 'image';

    /**
     * @var \Closure
     */
    protected $saveCallback;

    /**
     * @var array
     */
    protected $uploadValidationRules = ['required', 'image'];

    /**
     * @var string
     */
    protected $view = 'form.element.image';

    /**
     * @param Validator $validator
     */
    public function customValidation(Validator $validator)
    {
        $validator->after(function ($validator) {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = array_get($validator->attributes(), 'file');

            $size = getimagesize($file->getRealPath());

            if (! $size) {
                $validator->errors()->add('file', trans('sleeping_owl::validation.not_image'));
            }
        });
    }

    /**
     * Set.
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
     * @return \Closure|File|array
     */
    public function saveFile(UploadedFile $file, $disk, $path, $filename, array $settings)
    {
        if ($this->getSaveCallback()) {
            $callable = $this->getSaveCallback();

            return call_user_func($callable, [$file, $disk, $path, $filename, $settings]);
        }

        if (class_exists('Intervention\Image\Facades\Image') and (bool) getimagesize($file->getRealPath())) {
            $image = \Intervention\Image\Facades\Image::make($file);

            foreach ($settings as $method => $args) {
                call_user_func_array([$image, $method], $args);
            }

            $value = $path.'/'.$filename;

            $storage = Storage::disk($disk);

            $storage->put($value, $image->stream());

            return ['path' => $storage->url($value), 'value' => $value];
        }

        return parent::saveFile($file, $disk, $path, $filename, $settings);
    }

    /**
     * @param UploadedFile $file
     *
     * @return string
     */
    public function defaultUploadPath(UploadedFile $file)
    {
        return config('sleeping_owl.imagesUploadDirectory', 'images/uploads');
    }
}
