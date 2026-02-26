# Build & Deploy

## Instalação das Dependências

```bash
npm install
```

## Criar ZIP para Distribuição

```bash
npm run build
```

ou

```bash
npm run zip
```

ou

```bash
grunt compress
```

O arquivo ZIP será gerado em: `dist/moodle-management-1.0.0.zip`

## Instalação no WordPress

1. Execute `npm run build` para criar o ZIP
2. Acesse o WordPress: **Plugins > Adicionar Novo > Enviar Plugin**
3. Selecione o arquivo `dist/moodle-management-1.0.0.zip`
4. Clique em **Instalar Agora**
5. Ative o plugin

## Estrutura do ZIP

O arquivo ZIP contém todos os arquivos necessários do plugin, excluindo:
- node_modules/
- dist/
- .git/
- Arquivos de desenvolvimento (.gitignore, package.json, Gruntfile.js)
- Arquivos temporários e de cache

## Atualização de Versão

Para criar uma nova versão:

1. Atualize a versão em `moodle-management.php`:
   ```php
   * Version: 1.0.1
   define('MOODLE_MANAGEMENT_VERSION', '1.0.1');
   ```

2. Atualize a versão em `package.json`:
   ```json
   "version": "1.0.1"
   ```

3. Execute `npm run build` para gerar o novo ZIP
