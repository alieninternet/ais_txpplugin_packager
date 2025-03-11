# ais_txpplugin_packager - An alternative plugin packager for Textpattern plugins

This tool will produce a packed, gzipped, base64 encoded plugin package similar to that provided by zem_tpl and other tools, compatible with Textpattern 4.8.x.

This is not a plugin for Textpattern, nor is it a template to build a plugin from. Instead, the purpose is to allow Textpattern plugins to be developed with stand-alone package files rather than one monolothic file, such as the zem plugin template style).

The reason is that monolithic files are poor candidates for version control make it difficult to work efficiently with different file types, and increase maintenance complexity for complex plugins.

## How to use

This tool can be used either manually on the command line, for as a part of a GitHub workflow.

### As a command line tool

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

4. Execute the package tool, specifying the path to the plugin; it will output a text file appropriately encoded, with a name based on the plugin name and version in the manifest unless you override the output file name.
```shell
   php ais_txpplugin_packager.phar <plugin_path> [<output_file>]
```

For example:
```shell
   php ais_txpplugin_packager.phar ./path/to/xxx_plugin_name
```

5. Test your package in a test environment to ensure it is packed and unpacked correctly

6. Release your plugin!

### As a part of a GitHub workflow

Using a YAML workflow, you can run this packager without having to download it by using the `alieninternet/build-txpplugin-txt` GitHub action.

Here is a simple example ([from here](https://github.com/alieninternet/build-txpplugin-txt/blob/main/examples/simple.md)):

```yaml
on:
  release:
    type: [published]

permissions:
  contents: write

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Check-out repository
        uses: actions/checkout@v4

      - name: Build and release package
        uses: alieninternet/build-txpplugin-txt@v1
        with:
          folder: './path/to/xxx_plugin_name'
          release_files: 'true'
```

For more information, see the [alieninternet/build-txpplugin-txt](https://github.com/alieninternet/build-txpplugin-txt) project for more details.
