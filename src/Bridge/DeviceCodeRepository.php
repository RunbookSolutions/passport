<?php

namespace Laravel\Passport\Bridge;

use DateTimeImmutable;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge\Client;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;

class DeviceCodeRepository implements DeviceCodeRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * {@inheritdoc}
     */
    public function getNewDeviceCode(): DeviceCodeEntityInterface
    {
        return new DeviceCode();
    }

    /**
     * {@inheritdoc}
     */
    public function persistDeviceCode(DeviceCodeEntityInterface $deviceCodeEntity): void
    {
        $attributes = [
            //'id' => $deviceCodeEntity->getIdentifier(),
            'user_code' => $deviceCodeEntity->getUserCode(),
            'user_id' => $deviceCodeEntity->getUserIdentifier(),
            'client_id' => $deviceCodeEntity->getClient()->getIdentifier(),
            'scopes' => $this->formatScopesForStorage($deviceCodeEntity->getScopes()),
            'revoked' => false,
            'expires_at' => $deviceCodeEntity->getExpiryDateTime(),
        ];

        Passport::deviceCode()->updateOrCreate(
            ['id' => $deviceCodeEntity->getIdentifier()],
            $attributes
        );
        //setRawAttributes($attributes)->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeviceCodeEntityByDeviceCode(string $deviceCode): ?DeviceCodeEntityInterface
    {
        $record = Passport::deviceCode()->where('id', $deviceCode)->orWhere('user_code', $deviceCode)->first();

        if (!$record) {
            return null;
        }

        $deviceCode = new DeviceCode();
        $deviceCode->setIdentifier($record->id);
        $deviceCode->setUserCode($record->user_code);

        foreach (json_decode($record->scopes) as $scope) {
            $deviceCode->addScope($scope);
        }

        //($identifier, $name, $redirectUri, $isConfidential = false, $provider = null)
        $client = new Client($record->client->id, $record->client->name, $record->client->redirect, $record->client->confidential());
        $deviceCode->setClient($client);

        //setExpiryDateTime(DateTimeImmutable $dateTime)
        $deviceCode->setExpiryDateTime(new DateTimeImmutable($record->expires_at));

        $deviceCode->setUserIdentifier($record->user_id);

        $deviceCode->setUserApproved(!$record->revoked);

        return $deviceCode;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeDeviceCode($codeId): void
    {
        Passport::deviceCode()->where('id', $codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isDeviceCodeRevoked($codeId): bool
    {
        return Passport::deviceCode()->where('id', $codeId)->where('revoked', 1)->exists();
    }
}