<?php

declare(strict_types=1);

/**
 * The browser translates through Nova's JSON translations, and a missing key shows the
 * English key instead of failing. So every language the package ships carries every key.
 */
function translationsOf(string $locale): array
{
    return json_decode(
        (string) file_get_contents(__DIR__.'/../../lang/'.$locale.'.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

it('ships the same keys in every language', function () {
    expect(array_keys(translationsOf('sk')))->toEqualCanonicalizing(array_keys(translationsOf('en')));
});

it('translates every key into Slovak', function () {
    foreach (translationsOf('sk') as $key => $value) {
        expect($value)->toBeString()->not->toBe('', "sk.json leaves \"{$key}\" empty");
    }
});

it('ships the message shown when a row action cannot load its form', function (string $locale) {
    expect(translationsOf($locale))
        ->toHaveKey('The form of this action could not be loaded for this row.');
})->with(['en', 'sk']);
