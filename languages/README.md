# Translation Guide for Hydra Booking Customization

This directory contains the translation files for the Hydra Booking Customization plugin.

## Files

- `hydra-booking-customization.pot` - Template file containing all translatable strings
- Language-specific `.po` and `.mo` files will be placed here

## How to Translate

### Method 1: Using Poedit (Recommended)

1. Download and install [Poedit](https://poedit.net/)
2. Open Poedit and create a new translation from the `.pot` file
3. Select your target language
4. Translate all the strings
5. Save the file - this will create both `.po` and `.mo` files
6. Place the files in this directory with the naming convention:
   - `hydra-booking-customization-{locale}.po`
   - `hydra-booking-customization-{locale}.mo`

### Method 2: Using WordPress.org Translation Platform

If this plugin is hosted on WordPress.org, you can contribute translations through the official translation platform.

### Method 3: Manual Translation

1. Copy the `hydra-booking-customization.pot` file
2. Rename it to `hydra-booking-customization-{locale}.po` (e.g., `hydra-booking-customization-es_ES.po` for Spanish)
3. Edit the file and translate the `msgstr` entries
4. Use a tool like `msgfmt` to compile the `.po` file to `.mo`

## Language Codes

Use WordPress locale codes for naming your translation files:

- Spanish (Spain): `es_ES`
- French (France): `fr_FR`
- German: `de_DE`
- Italian: `it_IT`
- Portuguese (Brazil): `pt_BR`
- Dutch: `nl_NL`
- Russian: `ru_RU`
- Japanese: `ja`
- Chinese (Simplified): `zh_CN`
- Chinese (Traditional): `zh_TW`

## Testing Translations

1. Place your `.mo` file in this directory
2. Change your WordPress site language to match your translation
3. Visit the attendee and host dashboards to verify translations appear correctly
4. Test all UI elements including:
   - Dashboard headers and navigation
   - Form labels and buttons
   - Status messages and notifications
   - Error messages

## Translation Context

The plugin includes translations for:

- **Dashboard Interface**: Headers, navigation, buttons
- **Form Fields**: Labels for profile and booking forms
- **Status Messages**: Success, error, and informational messages
- **Booking Management**: Booking statuses, actions, and details
- **Meeting Integration**: Meeting-related text and notifications

## Contributing

If you've created a translation and would like to contribute it back to the plugin, please contact the plugin maintainer or submit it through the appropriate channels.

## Notes

- All strings use the text domain `hydra-booking-customization`
- The plugin supports both PHP and JavaScript translations
- Vue.js components use WordPress i18n functions for client-side translations
- Make sure to test translations in both attendee and host dashboards