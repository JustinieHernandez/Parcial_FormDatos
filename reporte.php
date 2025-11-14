<?php
// reporte.php
// (Rubro 14: Reporte de datos guardados)
require_once 'Conexion.php';

// Inicializar la conexión a la base de datos
$db = new DB_Conexion();

// Consulta para obtener todos los datos, incluyendo ambas fechas
$sql_reporte = "
    SELECT 
        i.nombre, i.apellido, i.edad, i.sexo, p.nombre_pais AS pais_residencia, 
        i.nacionalidad, i.correo, i.celular, i.fecha_registro, i.fecha_formulario, i.id_inscriptor
    FROM 
        inscriptores i
    JOIN 
        paises p ON i.id_pais_residencia = p.id_pais
    ORDER BY 
        i.fecha_registro DESC
";
$resultado = $db->obtener_datos($sql_reporte);

// Obtener Áreas de Interés para cada inscriptor
$temas_inscriptores = [];
if ($resultado && $resultado->num_rows > 0) {
    // Recorrer los resultados para obtener IDs de inscriptores
    $inscriptor_ids = [];
    $data_rows = [];
    while($row = $resultado->fetch_assoc()) {
        $inscriptor_ids[] = $row['id_inscriptor'];
        $data_rows[] = $row; // Guardar todas las filas para no perder el resultado
    }

    // Volver a cargar el resultado en una variable iterable
    $resultado = new ArrayIterator($data_rows);

    // Si hay inscriptores, obtener sus temas
    if (!empty($inscriptor_ids)) {
        $ids_str = implode(',', $inscriptor_ids);
        $sql_temas = "
            SELECT 
                ia.id_inscriptor, ai.nombre_area 
            FROM 
                inscriptor_areas ia
            JOIN 
                areas_interes ai ON ia.id_area = ai.id_area
            WHERE 
                ia.id_inscriptor IN ({$ids_str})
        ";
        $res_temas = $db->obtener_datos($sql_temas);

        // Agrupar los temas por inscriptor
        if ($res_temas && $res_temas->num_rows > 0) {
            while($tema = $res_temas->fetch_assoc()) {
                $id = $tema['id_inscriptor'];
                if (!isset($temas_inscriptores[$id])) {
                    $temas_inscriptores[$id] = [];
                }
                $temas_inscriptores[$id][] = $tema['nombre_area'];
            }
        }
    }
}

$db->cerrar_conexion();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscriptores</title>
    <link rel="stylesheet" href="style.css"> 
    <style>
        .contenedor-reporte {
            margin: 20px auto;
            padding: 20px;
            max-width: 95%; 
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow-x: auto; /* Permite scroll horizontal en dispositivos pequeños */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 800px; /* Asegura un ancho mínimo para que la tabla no se colapse */
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #3f51b5; /* Azul primario */
            color: white;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        .boton-volver {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #ff5722; /* Naranja para el botón */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .boton-volver:hover {
            background-color: #e64a19;
        }
    </style>
</head>
<body>
    <div class="contenedor-reporte">
        <h2>Reporte Detallado de Inscriptores</h2>

        <a href="index.php" class="boton-volver">Volver al Formulario</a>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Edad</th>
                    <th>Correo</th>
                    <th>Celular</th>
                    <th>País Res.</th>
                    <th>Nacionalidad</th>
                    <th>Temas Interés</th>
                    <th>Fecha Form. (Usuario)</th> 
                    <th>Fecha Reg. (Sistema)</th> 
                </tr>
            </thead>
            <tbody>
                <?php if (isset($resultado) && $resultado instanceof ArrayIterator && $resultado->count() > 0): ?>
                    <?php foreach ($resultado as $inscriptor): ?>
                        <?php 
                            $id_inscriptor = $inscriptor['id_inscriptor'];
                            $temas_array = $temas_inscriptores[$id_inscriptor] ?? [];
                            $temas_str = implode(", ", $temas_array);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($id_inscriptor); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor['nombre'] . " " . $inscriptor['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor['edad']); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor['correo']); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor['celular']); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor['pais_residencia']); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor['nacionalidad']); ?></td>
                            <td><?php echo htmlspecialchars($temas_str); ?></td>
                            <!-- Muestra la fecha que el usuario seleccionó -->
                            <td><?php echo htmlspecialchars((new DateTime($inscriptor['fecha_formulario']))->format('d/m/Y')); ?></td>
                            <!-- Muestra la fecha y hora de la inserción del registro -->
                            <td><?php echo htmlspecialchars((new DateTime($inscriptor['fecha_registro']))->format('d/m/Y H:i')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">No se encontraron datos de inscriptores.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>