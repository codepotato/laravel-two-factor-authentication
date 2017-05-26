<?php

namespace MichaelDzjap\TwoFactorAuth\Providers;

use Exception;
use App\User;
use MessageBird\Client;
use MessageBird\Exceptions\RequestException;
use MessageBird\Objects\Verify;
use MichaelDzjap\TwoFactorAuth\Contracts\SMSToken;
use MichaelDzjap\TwoFactorAuth\Contracts\TwoFactorProvider;
use MichaelDzjap\TwoFactorAuth\Exceptions\TokenAlreadyProcessedException;
use MichaelDzjap\TwoFactorAuth\Exceptions\TokenExpiredException;
use MichaelDzjap\TwoFactorAuth\Exceptions\TokenInvalidException;

class MessageBirdVerify implements TwoFactorProvider, SMSToken
{
    /**
     * MessageBird client instance.
     *
     * @var Client
     */
    private $client;

    /**
     * MessageBirdVerify constructor.
     *
     * @param  Client $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Check if two-factor authentication is enabled for a user.
     *
     * @param  User $user
     * @return bool
     */
    public function enabled(User $user)
    {
        return $user->twoFactorAuth;
    }

    /**
     * Register a user with this provider.
     *
     * @param  User $user
     * @return void
     */
    public function register(User $user)
    {
        //
    }

    /**
     * Unregister a user with this provider.
     *
     * @param  User $user
     * @return bool
     */
    public function unregister(User $user)
    {
        $result = $this->client->verify->delete($user->getTwoFactorAuthId());
        $user->setTwoFactorAuthId(null);

        return $result;
    }

    /**
     * Determine if the token is valid.
     *
     * @param  User $user
     * @param  string $token
     * @return bool
     */
    public function verify(User $user, string $token)
    {
        // Parse potential MessageBird exceptions. Unfortunately a rather generic
        // RequestException is thrown both in the case of an expired token as well as
        // when the token is invalid or doesn't contain the right number of characters.
        // In the case of an expired token we want to redirect to the login screen,
        // whereas in the case of an invalid token we just want to notify the user
        // about this.
        try {
            $result = $this->client->verify->verify($user->getTwoFactorAuthId(), $token);
        } catch (RequestException $exception) {
            $message = $exception->getMessage();

            if ($message === 'Token should between 6 and 10 characters' || $message === 'The token is invalid.') {
                throw new TokenInvalidException($message);
            }

            if ($message === 'The token has expired.') {
                throw new TokenExpiredException($message);
            }

            if ($message === 'The token has already been processed.') {
                throw new TokenAlreadyProcessedException($message);
            }

            // Re-throw exception if there was no match
            throw $exception;
        }

        if ($result->getStatus() === Verify::STATUS_VERIFIED) {
            return true;
        }

        return false;
    }

    /**
     * Send a user a two-factor authentication token via SMS.
     *
     * @param  User $user
     * @return void
     * @throws Exception $exception
     */
    public function sendSMSToken(User $user)
    {
        if (!$user->mobile) {
            throw new Exception("No mobile phone number found for user {$user->id}.");
        }

        $verify = new Verify;
        $verify->recipient = $user->mobile;

        $result = $this->client->verify->create(
            $verify,
            config('twofactor-auth.providers.messagebird.options')
        );

        $user->setTwoFactorAuthId($result->getId());
    }
}