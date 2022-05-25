<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\Authenticator\Infrastructure\Http\Controller;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\TwoFactorAuthenticator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\TwoFactorChallengeExpired;

use function dirname;
use function ob_start;
use function esc_attr;
use function admin_url;
use function ob_get_clean;

final class TwoFactorChallengeController extends Controller
{
    
    private TwoFactorChallengeService $challenge_service;
    
    public function __construct(TwoFactorChallengeService $challenge_service) {
        $this->challenge_service = $challenge_service;
    }
    
    public function challenge(Request $request) :Response
    {
		if($request->isGet()) {
			$token = (string) $request->query(TwoFactorAuthenticator::CHALLENGE_ID);
			
			return $this->respondWith()->view(
					dirname(__DIR__).'/templates/snicco/auth/2fa-challenge.php',
					[
							'redirect_to' => $redirect = (string) $request->query('redirect_to', admin_url()),
							'challenge_id' => $token,
							'input_fields' => $this->inputFields($redirect, $token),
							'post_url' => $request->path(),
					]
			);
		}
		
        $token = (string) $request->post(TwoFactorAuthenticator::CHALLENGE_ID);
	
	    try {
		    $challenged_user = $this->challenge_service->getChallengedUser($token);
	    }catch (TwoFactorChallengeExpired $e) {
			return $this->respondWith()->html("This challenge is expired. <a href=/wp-login.php'>Please login again</a>")
			                           ->withStatus(422);
	    }
		
		return $this->respondWith()->html("Challenged user $challenged_user");
    }
    
    private function inputFields(string $redirect, string $token) :string
    {
        ob_start();
        ?>
	    <label>
		    Enter your one time password
		    <input
				    type='text'
				    name='<?= esc_attr(TwoFactorAuthenticator::OTP_KEY) ?>'
				    inputmode='numeric'
				    pattern='[0-9]*'
				    autocomplete='one-time-code'
		    />
	    </label>
	    <label>
		    Or use a backup code
		    <input
				    type='text'
				    name='<?= esc_attr(TwoFactorAuthenticator::BACKUP_CODE_KEY) ?>'
		    />
	    </label>
	    <input type='hidden' name='redirect_to' value="<?= $redirect ?>">
        <input type='hidden' name='<?= esc_attr(TwoFactorAuthenticator::CHALLENGE_ID) ?>' value="<?= $token ?>">
        <?php
        return (string) ob_get_clean();
    }
    
}