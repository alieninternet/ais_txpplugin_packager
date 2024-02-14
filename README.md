# ais_txpplugin_packager - An alternative plugin packager for Textpattern plugins

This tool will produce a packed, gzipped, base64 encoded plugin package similar to that provided by zem_tpl and other tools, compatible with Textpattern 4.8.x.

This is not a plugin for Textpattern, nor is it a template to build a plugin from. Instead, the purpose is to allow Textpattern plugins to be developed with stand-alone package files rather than one monolothic file, such as the zem plugin template style).

The reason is that monolithic files are poor candidates for version control make it difficult to work efficiently with different file types, and increase maintenance complexity for complex plugins.

## How to use

1. Create a folder for the plugin with the following file structure :
```
    plugin_name/
        plugin_name.php       - Your plugin code (required)
        manifest.json         - Plugin manifest file (required)
        textpack.txp          - Textpack file (optional)
        help.textile          - Textile formatted help (optional, recommended)
        help.help             - HTML formatted help (optional)
        data.txp              - Included resources (optional)
```

3. Write your plugin!

4. Execute the package tool, specifying the path to the plugin; it will output a text file appropriately encoded, based on the plugin name and version in the manifest. If desired, you may specify an output filename and location:
```
   php ais_txpplugin_packager.php <plugin_path> [<output_file>]
```

For example:
```
   php ais_txpplugin_packager.php /var/www/txp/sites/dev/admin/plugins/xxx_plugin_name
```

5. Test your package in a test environment to ensure it is packed and unpacked correctly

6. Release your plugin!
