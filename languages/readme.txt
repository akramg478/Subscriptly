# Subscriptly translations

Text domain: `subscriptly`  
Folder: `languages/`

## Files

| File | Purpose |
|------|---------|
| `subscriptly.pot` | Template with all translatable strings (for translators) |
| `subscriptly-{locale}.po` | Translation for a locale (e.g. `subscriptly-fr_FR.po`) |
| `subscriptly-{locale}.mo` | Compiled binary loaded by WordPress |

WordPress loads: `languages/subscriptly-{locale}.mo`  
Example: `subscriptly-en_GB.mo`, `subscriptly-fr_FR.mo`, `subscriptly-nl_NL.mo`

## Requirements

- [WP-CLI](https://wp-cli.org/) with the `i18n` command (included in modern WP-CLI builds)
- Optional: [Poedit](https://poedit.net/) or Loco Translate to edit `.po` files

## Commands (from plugin root)

### 1. Generate / update the template (.pot)

```powershell
cd E:\laragon\www\azistar\wp-content\plugins\subscriptly
wp i18n make-pot . languages/subscriptly.pot --domain=subscriptly --exclude=vendor,tests,node_modules
```

Or use the helper script:

```powershell
bin\i18n.bat pot
```

### 2. Start a new language

```powershell
bin\i18n.bat new fr_FR
```

Then open `languages/subscriptly-fr_FR.po` in Poedit and translate.

### 3. Compile .mo files (after editing .po)

```powershell
wp i18n make-mo languages/
```

Or:

```powershell
bin\i18n.bat mo
```

### 4. Regenerate everything

```powershell
bin\i18n.bat all
```

## WordPress.org

After your plugin is approved, community translations are managed on  
https://translate.wordpress.org/projects/wp-plugins/subscriptly  

You do not need to ship `.po`/`.mo` files in the plugin zip for WordPress.org — include `subscriptly.pot` so translators can contribute.

## Composer

```powershell
composer run i18n:pot
composer run i18n:mo
```
