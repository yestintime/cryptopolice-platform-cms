<?php namespace Cryptopolice\uploader\Traits;

use Input;
use Event;
use Request;
use Response;
use Exception;
use Validator;
use System\Models\File;
use ValidationException;
use ApplicationException;
use October\Rain\Support\Collection;
use October\Rain\Database\Attach\Resizer;
use ToughDeveloper\ImageResizer\Classes\Image;

trait ComponentUtils
{

    /**
     * @var Model
     */
    public $model;

    /**
     * @var string
     */
    public $attribute;

    /**
     * @var string
     */
    public $sessionKey;

    public function bindModel($attribute, $model)
    {
        if (is_callable($model))
            $model = $model();

        $this->model = $model;
        $this->attribute = $attribute;

        if ($this->model) {
            $relationType = $this->model->getRelationType($attribute);
            $this->isMulti = ($relationType == 'attachMany' || $relationType == 'morphMany');
            $this->isBound = true;
        }
    }

    public function setPopulated($model)
    {
        $list = $this->isMulti ? $model : new Collection([$model]);

        $list->each(function ($file) {
            $this->decorateFileAttributes($file);
        });

        $this->fileList = $list;
        $this->singleFile = $list->first();
    }

    public function isPopulated()
    {
        if (!$this->fileList) {
            return false;
        }

        return $this->fileList->count() > 0;
    }

    public function getFileList()
    {
        /*
         * Use deferred bindings
         */
        if ($sessionKey = $this->getSessionKey()) {
            $list = $deferredQuery = $this->model
                ->{$this->attribute}()
                ->withDeferred($sessionKey)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $list = $this->model
                ->{$this->attribute}()
                ->orderBy('id', 'desc')
                ->get();
        }

        if (!$list) {
            $list = new Collection;
        }

        /*
         * Decorate each file with thumb
         */
        $list->each(function ($file) {
            $this->decorateFileAttributes($file);
        });

        return $list;
    }

    protected function compress($source, $destination, $quality)
    {

        $info = getimagesize($source);

        if ($info['mime'] == 'image/jpeg')
            $image = imagecreatefromjpeg($source);

        elseif ($info['mime'] == 'image/png')
            $image = imagecreatefrompng($source);

        imagejpeg($image, $destination, $quality);

        return $destination;
    }

    protected function checkUploadAction()
    {

        if (!($uniqueId = Request::header('X-OCTOBER-FILEUPLOAD')) || $uniqueId != $this->alias) {
            return;
        }

        try {
            if (!Input::hasFile('file_data')) {
                throw new ApplicationException('File missing from request');
            }

            $uploadedFile = Input::file('file_data');

            $validation = Validator::make(
                ['file_data' => $uploadedFile], // this is the single image instance
                ['file_data' => 'required|image|mimes:jpg,jpeg,png|max:1500'] // this is the rule for a single image
            );

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new ApplicationException(sprintf('File %s is not valid.', $uploadedFile->getClientOriginalName()));
            }

            $this->compress(Input::file('file_data')->getRealPath(), Input::file('file_data')->getRealPath(), 75);

            $file = new File;
            $file->data = Input::file('file_data');
            $file->is_public = true;
            $file->save();


            if ($this->isMulti) {
                $this->model->{$this->attribute}()->add($file, $this->getSessionKey());
            } else {
                $this->model->{$this->attribute}()->save($file, $this->getSessionKey());
            }

            $file = $this->decorateFileAttributes($file);

            $result = [
                'id' => $file->id,
                'thumb' => $file->thumbUrl,
                'path' => $file->pathUrl
            ];

            return Response::json($result, 200);

        } catch (Exception $ex) {
            return Response::json($ex->getMessage(), 400);
        }
    }

    public function getSessionKey()
    {
        return !!$this->property('deferredBinding')
            ? post('_session_key', $this->sessionKey)
            : null;
    }

}