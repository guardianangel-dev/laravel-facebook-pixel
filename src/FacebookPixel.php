<?php

namespace Combindma\FacebookPixel;

use Exception;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Macroable;

class FacebookPixel
{
    use Macroable;

    protected bool $enabled;

    protected string $pixelId;

    protected string $token;

    protected string $sessionKey;

    protected EventLayer $eventLayer;

    protected EventLayer $customEventLayer;

    protected EventLayer $flashEventLayer;
    protected EventLayer $inertiaEventLayer;

    public function __construct()
    {
        $this->enabled = config('facebook-pixel.enabled');
        $this->pixelId = config('facebook-pixel.facebook_pixel_id');
        $this->token = config('facebook-pixel.token');
        $this->sessionKey = config('facebook-pixel.sessionKey');
        $this->eventLayer = new EventLayer();
        $this->customEventLayer = new EventLayer();
        $this->flashEventLayer = new EventLayer();
        $this->inertiaEventLayer = new EventLayer();
    }

    public function pixelId()
    {
        return $this->pixelId;
    }

    public function sessionKey()
    {
        return $this->sessionKey;
    }

    public function token()
    {
        return $this->token;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function setPixelId(int|string $id): void
    {
        $this->pixelId = $id;
    }

    /**
     * Add event to the event layer.
     */
    public function track(string $eventName, array $parameters = []): void
    {
        $this->eventLayer->set($eventName, $parameters);
    }

    /**
     * Add custom event to the event layer.
     */
    public function trackCustom(string $eventName, array $parameters = []): void
    {
        $this->customEventLayer->set($eventName, $parameters);
    }

    /**
     * Add event data to the event layer for the next request.
     */
    public function flashEvent(string $eventName, array $parameters = []): void
    {
        $this->flashEventLayer->set($eventName, $parameters);
    }

    /**
     * Add custom event to the inertia event layer.
     */
    public function trackInertia(string $eventName, array $parameters = []): void
    {
        $this->inertiaEventLayer->set($eventName, $parameters);
    }

    /**
     * Send request using Conversions API
     */
    public function send(string $eventName, string $sourceUrl, UserData $userData, CustomData $customData)
    {
        if (! $this->isEnabled()) {
            return null;
        }
        if (empty($this->token())) {
            throw new Exception('You need to set a token in your .env file to use the Conversions API.');
        }

        $api = Api::init(null, null, $this->token);
        $api->setLogger(new CurlLogger());

        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setEventSourceUrl($sourceUrl)
            ->setUserData($userData)
            ->setCustomData($customData)
            ->setActionSource(ActionSource::WEBSITE);

        $events = [];
        array_push($events, $event);

        $request = (new EventRequest($this->pixelId()))->setEvents($events);

        try {
            return $request->execute();
        } catch (Exception $e) {
            Log::error($e);
        }

        return null;
    }

    /**
     * Merge array data with the event layer.
     */
    public function merge(array $eventSession)
    {
        $this->eventLayer->merge($eventSession);
    }

    /**
     * Retrieve the event layer.
     */
    public function getEventLayer(): EventLayer
    {
        return $this->eventLayer;
    }

    /**
     * Retrieve custom event layer.
     */
    public function getCustomEventLayer(): EventLayer
    {
        return $this->customEventLayer;
    }

    /**
     * Retrieve inertia event layer.
     */
    public function getInertiaEventLayer(): EventLayer
    {
        return $this->inertiaEventLayer;
    }

    /**
     * Retrieve the event layer's data for the next request.
     */
    public function getFlashedEvent(): array
    {
        return $this->flashEventLayer->toArray();
    }


    /**
     * Retrieve the email to use it advanced matching.
     * To use advanced matching we will get the email if the user is authenticated
     */
    public function getEmail()
    {
        if (Auth::check()) {
            return Auth::user()->email;
        }

        return null;
    }

    public function clear(): void
    {
        $this->eventLayer = new EventLayer();
    }
}
