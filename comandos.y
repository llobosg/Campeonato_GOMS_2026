
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
  -e "DESCRIBE jugadores;"

    < database/schema.sql

mysql://root:bEPuLqZTUMZclzfaaBMQxkvMyNGVHcaH@zephyr.proxy.rlwy.net:55231/railway


git add .
git commit -m "fix: balón fifa"
git push origin main




// Lista de Encargados
$encargados = [
    'Trancapelotas FC' => ['nombre' => 'Cristobal Arriagada', 'email' => 'CArri013@contratistas.codelco.cl'],
    'Pem-K-Zo'        => ['nombre' => 'Camilo Fernández', 'email' => 'CFern055@contratistas.codelco.cl'],
    'Los Mundiales' => ['nombre' => 'Santiago Medina', 'email' => 'SMedi002@codelco.cl'],
    'Los Galacticos'    => ['nombre' => 'Dennis Garrido', 'email' => 'DGarr005@contratistas.codelco.cl'],
    'Mas Menos 1 Metro FC' => ['nombre' => 'Fabian Poblete', 'email' => 'FPobl007@codelco.cl'],
    'Calidad Prime' => ['nombre' => 'Luis Hernández', 'email' => 'LHern019@contratistas.codelco.cl'],
    'Los Desquinchadores' => ['nombre' => 'Carlos Rodríguez', 'email' => 'CRodr069@contratistas.codelco.cl'],
    'Macizo United'   => ['nombre' => 'Chantal González', 'email' => 'CGonz213@contratistas.codelco.cl'],
    'Deportivo NdC' => ['nombre' => 'Brian Arancibia', 'email' => 'BAran005@contratistas.codelco.cl'],
    'Jaque Boys'    => ['nombre' => 'Jaqueline Marín', 'email' => 'JMari019@codelco.cl'],
];

