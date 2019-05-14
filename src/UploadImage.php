<?php

namespace lakerLS\HTMLfileManager;

use himiklab\thumbnail\EasyThumbnailImage;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Прежде всего разрабатывалось для CKEditor и ELfinder, но так же может использоваться с любым аналогичным
 * html редактором и файловым менеджером.
 * С помощью Elfinder добавляются необходимые изображения в папку $imageGlobal, из которой изображения
 * добавляются в текстовые поля CKEditor'а. При создании/редактировании/удалении записи изображения перемещаются и
 * создаются миниатюры. Если запись содержащая изображение или непосредственно сама картинка будет удалена, изображение
 * будет перемещено в папку $imageGlobal, тем самым есть возможность повторного использования изображения.
 *
 * Для создания миниатюры изображения используется EasyThumbnailImage
 * https://github.com/himiklab/yii2-easy-thumbnail-image-helper
 */
class UploadImage extends Behavior
{
    /**
     * Обязательно передаем список полей, в которых необходимо обрабатывать изображения.
     * Пример: ['image', 'text']
     * @var array $fields
     */
    public $fields;

    /**
     * Путь к "корневой" папке, куда пользователь может добавлять изображения с помощью
     * загрузчика изображений/файлов и откуда визуальный редактор берет картинки.
     */
    public $imageGlobal = 'upload/global';

    // Путь, по которому данный компонент сохраняет оригинал изображения.
    public $imageFull = 'upload/image_full';

    // Путь, по которому данный компонент сохраняет миниатюру изображения.
    public $imageMini = 'upload/image_mini';

    // Если произошла ошибка при обработке изображения, то изображение заменится на указанное.
    public $imageNotFound = 'default-image_207_200.jpg';

    /*
     * Пути до папок, в которых лежат изображения. В папках, которые указанны в $imageFull и $imageMini будет
     * дополнительно создана директория, которая имеет название взятое из первых двух символов в имени изображения.
     * Это необходимо для того, что бы ускорить доступ к файлам, если изображений большое количество.
     */
    private $folderFull;
    private $folderMini;

    // Полные пути с именем изображения.
    private $imgGlobal;
    private $imgFull;
    private $imgMini;

    /**
     * В зависимости от события производятся следующие операции с фото:
     * BEFORE_INSERT перемещает изображение из папки $imageGlobal в $imageFull и создает миниатюру в $imageMini.
     *
     * BEFORE_UPDATE делает все действия как и BEFORE_INSERT, при этом существующие изображения записи переносятся в
     * папку $imageGlobal из $imageFull, удаляется миниатюра.
     *
     * AFTER_DELETE переносит изображения из $imageFull в $imageGlobal, удаляет миниатюру в $imageMini.
     *
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'choiceEvent',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'choiceEvent',
            ActiveRecord::EVENT_AFTER_DELETE => 'choiceEvent',
        ];
    }

    /**
     * Функция осуществляет выбор необходимых действий в зависимости от события, производит операции над каждым полем,
     * которое было передано в public $fields.
     * @param object event
     */
    public function choiceEvent($event)
    {
        foreach ($this->owner->fields as $field) {
            if (isset($event->sender->$field)) {
                if ($event->name == 'beforeInsert') {
                    $event->sender->$field = $this->body(['upload' => $event->sender->$field]);
                } elseif ($event->name == 'beforeUpdate') {
                    $event->sender->$field = $this->body([
                        'delete' => $event->sender->getOldAttribute($field),
                        'upload' => $event->sender->$field,
                    ]);
                } else {
                    $this->body(['delete' => $event->sender->$field]);
                }
            }
        }
    }

    /**
     * Функция удаляет и сохраняет фотографии в зависимости от переданных параметров.
     * Если запись редактируется, то необходимо передавать как upload, так и delete.
     *
     * @param array $data поля с изображениями.
     * @return string возвращает тег изображения для сохранения в базу данных.
     */
    protected function body($data)
    {
        if (isset($data['delete'])) {
            preg_match_all('#<img.*src="(.*)".*>#isU', $data['delete'], $delete);

            foreach ($delete[1] as $value) {
                $pathInfo = pathinfo($value);
                $nameImg = $pathInfo['basename'];
                $folder = substr($nameImg, 0, 2);
                $this->allPath($folder, $pathInfo['basename'], $nameImg);
                $this->imageDelete();
            }
        }

        if (isset($data['upload'])) {
            preg_match_all('#<img.*src="(.*)".*>#isU', $data['upload'], $upload);

            foreach ($upload[1] as $key => $value) {
                $pathInfo = pathinfo($value);
                $time = time() + $key;
                $uniqueName = md5($time . $pathInfo['basename']);
                $nameImg = $uniqueName . '.' . $pathInfo['extension'];
                $folder = substr($nameImg, 0, 2);
                $this->allPath($folder, $pathInfo['basename'], $nameImg);
                $this->imageFull();
                $this->imageMini($upload[0][$key]);
                $data['upload'] = str_replace($value, '/' . $this->folderMini . '/' . $nameImg, $data['upload']);
            }

            return $data['upload'];
        }

        return false;
    }

    /**
     * При вызове данной функции создаются правильные пути к изображениям.
     *
     * @param string $folder папка, которая создается в функции imageFull,
     * наименована по первым 2 символам имени изображения.
     * @param string $basename оригинальное название изображения.
     * @param string $nameImg новое название изображения.
     */
    protected function allPath($folder, $basename, $nameImg)
    {
        // Пути до папок, в которых лежат изображения.
        $this->folderFull = $this->imageFull . '/' . $folder;
        $this->folderMini = $this->imageMini . '/' . $folder;

        // Полные пути изображений.
        $this->imgGlobal = $this->imageGlobal . '/' . $basename;
        $this->imgFull = $this->folderFull . '/' . $nameImg;
        $this->imgMini = $this->folderMini . '/' . $nameImg;
    }

    /**
     * Функция создает папки по первым двум символам в наименовании изображения и сохраняет оригинал.
     * ВАЖНО: функция оперирует путями изображений,
     * поэтому перед вызовом данной функции обязателен вызов функции allPath, которая эти пути создаст.
     */
    protected function imageFull()
    {
        if (!is_dir($this->folderFull)) {
            mkdir($this->folderFull, 0777);
        }
        if (!is_dir($this->folderMini)) {
            mkdir($this->folderMini, 0777);
        }
        if (file_exists($this->imgGlobal) === true) {
            rename($this->imgGlobal, $this->imgFull);
        } else {
            copy($this->imageNotFound, $this->imgFull);
        }
    }

    /**
     * Создание миниатюры из оригинала изображения.
     * Миниаютра создается исходя из полученных данных width и height в style.
     * При отстутствии данных в style миниатюра будет создана полноразмерной.
     *
     * @param string $upload
     */
    protected function imageMini($upload)
    {
        if (file_exists($this->imgFull) === true) {
            preg_match('/width:(\d+)/', $upload, $width);
            preg_match('/height:(\d+)/', $upload, $height);

            if (empty($width[1]) || empty($height[1])) {
                $originalSize = getimagesize($this->imgFull);
                $width[1] = $originalSize[0];
                $height[1] = $originalSize[1];
            }
            $createdMini = EasyThumbnailImage::thumbnailFileUrl(
                $this->imgFull,
                $width[1],
                $height[1],
                EasyThumbnailImage::THUMBNAIL_OUTBOUND,
                100
            );
            rename(substr($createdMini, 1), $this->imgMini);
        } else {
            copy($this->imageNotFound, $this->imgMini);
        }
    }

    /**
     * Функция перемещает оригинал в папку $imageGlobal и удаляет миниатюру.
     */
    protected function imageDelete()
    {
        if (file_exists($this->imgFull)) {
            if (file_exists($this->imgFull) === true && file_exists($this->imgMini) === true) {
                rename($this->imgFull, $this->imgGlobal);
                unlink($this->imgMini);
            } else {
                copy($this->imageNotFound, $this->imgGlobal);
            }
        }
    }
}