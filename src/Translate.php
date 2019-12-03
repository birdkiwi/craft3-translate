<?php

namespace mutation\translate;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\i18n\I18N;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use mutation\translate\controllers\TranslateController;
use mutation\translate\models\Settings;
use mutation\translate\models\SourceMessage;
use yii\base\Event;
use yii\i18n\MessageSource;
use yii\i18n\MissingTranslationEvent;

class Translate extends Plugin
{
    public $controllerMap = [
        'translate' => TranslateController::class,
    ];

    const UPDATE_TRANSLATIONS_PERMISSION = 'updateTranslations';

    public function init()
    {
        $this->name = Craft::t('translate', 'Translate');

        $this->initDbMessages();
        $this->initEvents();
    }

    protected function createSettingsModel()
    {
        return new Settings();
    }

    private function initDbMessages()
    {
        /** @var I18N $i18n */
        $i18n = Craft::$app->getComponents(false)['i18n'];

        foreach ($this->getSettings()->getCategories() as $category) {
            $i18n->translations[$category] = [
                'class' => DbMessageSource::class,
                'sourceLanguage' => 'en-US',
                'forceTranslation' => true,
            ];
        }

        Craft::$app->setComponents(
            [
                'i18n' => $i18n
            ]
        );
    }

    private function initEvents()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions['Translate'] = [
                    self::UPDATE_TRANSLATIONS_PERMISSION => [
                        'label' => 'Update translations',
                    ],
                ];
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['translate'] = 'translate/translate/index';
                $event->rules['translate/<localeId:[a-zA-Z\-]+>'] = 'translate/translate/index';
            }
        );

        Event::on(
            MessageSource::class,
            MessageSource::EVENT_MISSING_TRANSLATION,
            function (MissingTranslationEvent $event) {
                if ($event->message &&
                    in_array($event->category, $this->getSettings()->getCategories(), true)) {
                    $sourceMessage = SourceMessage::find()
                        ->where(array('message' => $event->message, 'category' => $event->category))
                        ->one();

                    if (!$sourceMessage) {
                        $sourceMessage = new SourceMessage();
                        $sourceMessage->category = $event->category;
                        $sourceMessage->message = $event->message;
                        $sourceMessage->save();
                    }
                }
            }
        );
    }
}
