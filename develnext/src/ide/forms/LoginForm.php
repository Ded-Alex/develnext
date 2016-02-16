<?php
namespace ide\forms;

use ide\account\api\AccountService;
use ide\account\api\ServiceResponse;
use ide\forms\mixins\SavableFormMixin;
use ide\Ide;
use ide\Logger;
use ide\ui\Notifications;
use ide\utils\UiUtils;
use php\gui\UXDesktop;
use php\gui\framework\AbstractForm;
use php\gui\UXButton;
use php\gui\UXDialog;
use php\gui\UXHyperlink;
use php\gui\UXImage;
use php\gui\UXPasswordField;
use php\gui\UXTextField;

/**
 * Class LoginForm
 * @package ide\forms
 *
 * @property UXImage $icon
 * @property UXTextField $emailField
 * @property UXPasswordField $passwordField
 * @property UXButton $loginButton
 * @property UXButton $loginVkButton
 * @property UXHyperlink $registerLink
 * @property UXHyperlink $forgetPasswordLink
 */
class LoginForm extends AbstractOnlineIdeForm
{
    //use SavableFormMixin;

    protected function init()
    {
        parent::init();

        $this->icon->image = Ide::get()->getImage('DevelNextIco.png')->image;
    }

    /**
     * @event loginButton.action
     * @event emailField.keyDown-Enter
     * @event passwordField.keyDown-Enter
     */
    public function actionLogin()
    {
        $this->showPreloader('Подождите ...');

        Ide::service()->account()->authAsync($this->emailField->text, $this->passwordField->text,
            function (ServiceResponse $response) {
                $this->hidePreloader();

                if ($response->isSuccess()) {
                    Ide::accountManager()->setAccessToken($response->data());
                    $this->hide();
                } else {
                    $message = $response->message();

                    switch ($message) {
                        case 'Validation':
                            $message = 'Введите все данные корректно';
                            break;
                    }

                    Notifications::error('Ошибка входа', $message);

                    if ($response->data() == 'RegisterConfirm') {
                        $dialog = new RegisterConfirmForm();
                        $dialog->setEmail($this->emailField->text);

                        if ($dialog->showDialog() && $dialog->getResult()) {
                            Ide::accountManager()->setAccessToken($dialog->getResult());
                            $this->hide();
                            Ide::accountManager()->updateAccount();
                            $this->hide();
                            return;
                        }
                    }

                }
            }
        );
    }

    /**
     * @event loginVkButton.action
     */
    public function actionLoginVk()
    {
        $this->showPreloader('Входим через VK ...');

        Ide::service()->account()->authVkAsync(function (ServiceResponse $response) {
            if ($response->isSuccess()) {
                $redirectForm = new LoginVkRedirectForm();

                $url = $response->data();

                $redirectForm->setAuthUrl($url);
                if ($redirectForm->showDialog()) {
                    Ide::accountManager()->setAccessToken($redirectForm->getResult());
                    $this->hide();
                }
            } else {
                $this->loginVkButton->enabled = false;
                Notifications::error('Ошибка входа', 'Сервис временно недоступен, попробуйте позже или используйте обычную регистрацию.');
            }

            $this->hidePreloader();
        });
    }

    /**
     * @event registerLink.action
     */
    public function actionRegister()
    {
        $registerForm = new RegisterForm();

        if ($registerForm->showDialog() && $registerForm->getResult()) {
            $this->hide();
        }
    }

    /**
     * @event forgetPasswordLink.action
     */
    public function actionRestore()
    {
        $dialog = new AccountRestorePasswordForm();
        $dialog->showAndWait();
    }
}