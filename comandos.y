
# Navegar al proyecto
cd /Applications/XAMPP/xamppfiles/htdocs/campeonatogoms2026

# Inicializar git (si no existe)
git init

# Agregar remote GitHub
git remote add origin https://github.com/llobosg/Campeonato_GOMS_2026.git

# Crear rama main
git checkout -b main


git add .
git commit -m "feat: initial project setup with database schema and architecture"
git push -u origin main