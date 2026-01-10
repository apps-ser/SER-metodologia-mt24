#!/bin/bash

# Diretório base do projeto
BASE_DIR="/home/thiago/Projetos/ser/SER-metodologia-mt24"
PLUGIN_NAME="metodologia-leitor-apreciador"
ZIP_FILE="$PLUGIN_NAME.zip"

echo "Iniciando geração do pacote ZIP..."

# Entrar no diretório base
cd "$BASE_DIR" || { echo "Erro ao acessar diretório base"; exit 1; }

# Remover versão anterior se existir dentro da pasta do plugin
rm -f "$PLUGIN_NAME/$ZIP_FILE"

# Gerar o ZIP usando Python (garante compatibilidade sem precisar de pacotes extras)
python3 -c "
import zipfile, os
plugin_dir = '$PLUGIN_NAME'
zip_name = '$PLUGIN_NAME/$ZIP_FILE'
parent_zip = '$ZIP_FILE'

def create_zip(target_path, source_dir):
    with zipfile.ZipFile(target_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(source_dir):
            # Excluir pastas indesejadas
            dirs[:] = [d for d in dirs if d not in ['.git', '.agent', '.gemini', 'node_modules']]
            for file in files:
                if file == '$ZIP_FILE': continue
                file_path = os.path.join(root, file)
                zipf.write(file_path, os.path.relpath(file_path, os.path.dirname(source_dir)))

# Criar dentro da pasta do plugin
create_zip('$PLUGIN_NAME/$ZIP_FILE', '$PLUGIN_NAME')
# Criar na raiz também para facilidade
create_zip('$ZIP_FILE', '$PLUGIN_NAME')
"

if [ $? -eq 0 ]; then
    echo "✅ Sucesso! Pacote gerado em: $BASE_DIR/$PLUGIN_NAME/$ZIP_FILE"
    ls -lh "$PLUGIN_NAME/$ZIP_FILE"
else
    echo "❌ Erro ao gerar o pacote ZIP via Python."
    exit 1
fi
