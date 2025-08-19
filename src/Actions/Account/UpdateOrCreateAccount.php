<?php

namespace Inovector\Mixpost\Actions\Account;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inovector\Mixpost\Events\Account\AccountAdded;
use Inovector\Mixpost\Events\Account\AccountUpdated;
use Inovector\Mixpost\Facades\WorkspaceManager;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Support\AccountSuffix;
use Inovector\Mixpost\Support\MediaUploader;

class UpdateOrCreateAccount
{
    public function __invoke(string $providerName, array $account, array $accessToken): void
    {
        $params = [
            'name' => $account['name'],
            'username' => $account['username'] ?? null,
            'media' => $this->media($account['image'], $providerName),
            'data' => $account['data'] ?? null,
            'access_token' => $accessToken,
        ];

        $record = Account::where('provider', $providerName)->where('provider_id', $account['id'])->first();

        if (!$record) {
            $account = Account::create(array_merge(
                [
                    'provider' => $providerName,
                    'provider_id' => $account['id'],
                    'authorized' => true,
                ],
                $params
            ));

            AccountAdded::dispatch($account);

            return;
        }

        // If the suffix has been edited, we keep it.
        $getRecordDataSuffix = Arr::get($record->data, AccountSuffix::key());

        if ($getRecordDataSuffix && AccountSuffix::isEdited($record->data)) {
            $params['data']['suffix'] = $getRecordDataSuffix;
        }

        $record->update(array_merge(
            [
                'authorized' => true,
            ],
            $params
        ));

        AccountUpdated::dispatch($record);
    }

    protected function media(string|null $imageUrl, string $providerName): array|null
    {
        if (!$imageUrl) {
            return null;
        }

        $info = pathinfo($imageUrl);
        $contents = file_get_contents($imageUrl);
        $file = '/tmp/' . Str::random(32);
        file_put_contents($file, $contents);

        $file = new UploadedFile($file, $info['basename']);
        $prefix = WorkspaceManager::current()->uuid;
        $path = "$prefix/avatars/$providerName";

        $upload = MediaUploader::fromFile($file)->path($path)->upload();

        return [
            'disk' => $upload['disk'],
            'path' => $upload['path']
        ];
    }
}
