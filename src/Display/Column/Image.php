<?php

namespace SleepingOwl\Admin\Display\Column;

use Storage;

class Image extends NamedColumn
{
    /**
     * @var string
     */
    protected $imageWidth = '80px';

    /**
     * @var string
     */
    protected $disk;

    /**
     * @var string
     */
    protected $view = 'column.image';

    /**
     * @return string
     */
    public function getImageWidth()
    {
        return $this->imageWidth;
    }

    /**
     * @param string $width
     *
     * @return $this
     */
    public function setImageWidth($width)
    {
        $this->imageWidth = $width;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisk()
    {
        if (!$this->disk) {
            return $this->defaultDisk();
        }

        return $this->disk;
    }

    /**
     * @param string $disk
     *
     * @return $this
     */
    public function setUploadDisk($disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * @return string
     */
    public function defaultDisk()
    {
        return config('sleeping_owl.filesUploadDisk', 'public');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $value = $this->getModelValue();

        if (! empty($value) && (strpos($value, '://') === false)) {
            $value = is_string($value) ? Storage::disk($this->getDisk())->url($value) : null;
        }

        return parent::toArray() + [
            'value'  => $value,
            'imageWidth'  => $this->getImageWidth(),
        ];
    }
}
