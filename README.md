<h1 align="center">
    yii2-upload-image
</h1>

[![Stable Version](https://poser.pugx.org/laker-ls/yii2-upload-image/v/stable)](https://packagist.org/packages/laker-ls/yii2-upload-image)
[![Unstable Version](https://poser.pugx.org/laker-ls/yii2-upload-image/v/unstable)](https://packagist.org/packages/laker-ls/yii2-upload-image)
[![License](https://poser.pugx.org/laker-ls/yii2-upload-image/license)](https://packagist.org/packages/laker-ls/yii2-upload-image)
[![Total Downloads](https://poser.pugx.org/laker-ls/yii2-upload-image/downloads)](https://packagist.org/packages/laker-ls/yii2-upload-image)

Это расширение является поведением для yii2. Используется для загрузки изображений при использовании любых HTML редакторов и файловых менеджеров,
например CKEditor и ELfinder. HTML редакторы передают изображение в строке следующего вида `<img alt="" src="/upload/global/test.jpg" style="height:853px; width:1280px" />`, 
где в `src` указывается фактический путь изображения, которое загружено на сервер с помощью файлового менеджера, а в `style` передаются размеры миниатюры. 
Расширение ищет изображения в указанных полях и перемещает их в другие папки, по умолчанию `/upload/image_full`, а так же создает миниатюры, по умолчанию 
в `/upload/image_mini`. Размеры миниатюры получаются из `style`, если не указаны, то миниатюра будет реальных размеров, пути изображений заменяются на актуальные пути миниатюр. При удалении записи 
приложение удаляет миниатюру и переносит оригинал изображения в место, куда загружает изображения файловый менеджер.

## Установка

Рекомендуемый способ установки этого расширения является использование [composer](http://getcomposer.org/download/).
Проверьте [composer.json](https://github.com/laker-ls/yii2-nested-set-menu/blob/master/composer.json) на предмет требований и зависимостей данного расширения.

Для установки запустите

```
$ php composer.phar require laker-ls/yii2-upload-image "~1.0.2"
```

или добавьте в `composer.json` в раздел `require` следующую строку

```
"laker-ls/yii2-upload-image": "~1.0.2"
```

> Смотрите [список изменений](https://github.com/laker-ls/yii2-nested-set-menu/blob/master/CHANGE.md) для подробной информации о версиях.

## Использование

В модели необходимо подключить поведение и задать параметр `fields`.

Обязательные параметры: 
- `fields` должен содержать массив где перечислены поля, в которых необходимо обрабатывать изображения после HTML редактора.

Не обязательные параметры:
- `imageGlobal` содержит путь, в который файловый менеджер сохраняет изображения.
- `imageFull` содержит путь, в который поведение будет сохранять изображения.
- `imageMini` содержит путь, куда будет создана миниатюра.
- `imageNotFound` указан путь к изображению, которое будет использовано в случае ошибки обработки изображения.

> ВНИМАНИЕ: путь необходимо указывать без слеша в конце. Запись `upload/folder/` является не корректной.

```php
use lakerLS\HTMLfileManager\UploadImage;

public function behaviors()
    {
        return [
            'uploadImage' => [
                'class' => UploadImage::class,
                'fields' => ['image', 'text'],
            ]
        ];
    }
```

## Лицензия

**yii2-upload-image** выпущено по лицензии BSD-3-Clause. Ознакомиться можно в файле `LICENSE.md`.
