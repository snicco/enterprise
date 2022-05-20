<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Password\Event;

use WP_Error;
use stdClass;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;

use function is_string;

final class UpdatingUserInAdminArea implements MappedHook
{
    
    use ClassAsPayload;
    use ClassAsName;
    
    private WP_Error $errors;
    private bool     $is_user_update;
    private int      $wp_user_id;
    
    public function __construct(WP_Error $errors, bool $is_user_update, stdClass $wp_user)
    {
        $this->errors = $errors;
        $this->is_user_update = $is_user_update;
        $this->wp_user_id = (int) $wp_user->ID;
    }
    
    public function addError(string $key, string $message) :void
    {
        $this->errors->add($key, $message);
    }
    
    public function userId() :int
    {
        return $this->wp_user_id;
    }
    
    public function shouldDispatch() :bool
    {
        return isset($_POST['pass1'])
               && is_string($_POST['pass1'])
               && isset($_POST['pass2'])
               && is_string($_POST['pass2'])
               && $_POST['pass1'] === $_POST['pass2'];
    }
    
}