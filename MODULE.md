# CONFIG STRUCTURE

## Upload custom module structure.

    1. Each custom module must be archived by ZIP
    2. Each custom module must contains config.php into a root of archived module

    Example of upload config.php :

        return [
            'module_path' => 'module/Example', // it defines where the module directory located
            'layout_path' => 'layout/example'  // it defines where the module layout directory located
        ];