
# Navegar al proyecto
cd /Applications/XAMPP/xamppfiles/htdocs/campeonatogoms2026

# Inicializar git (si no existe)
git init

# Agregar remote GitHub
git remote add origin https://github.com/llobosg/Campeonato_GOMS_2026.git

# Crear rama main
git checkout -b main

/Applications/XAMPP/xamppfiles/bin/mysql \
  --host=zephyr.proxy.rlwy.net \
  --port=55231 \
  --user=root \
  --password=bEPuLqZTUMZclzfaaBMQxkvMyNGVHcaH \
  --database=railway \
  -e "DESCRIBE equipos;"

    < database/schema.sql

mysql://root:bEPuLqZTUMZclzfaaBMQxkvMyNGVHcaH@zephyr.proxy.rlwy.net:55231/railway


git add .
git commit -m "fix: populate empty layout files with correct HTML structure and CSS links"
git push origin main


