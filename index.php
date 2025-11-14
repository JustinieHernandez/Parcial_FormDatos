<?php
// index.php (VERSIÓN CON VALIDACIONES SEPARADAS Y bind_param CORREGIDO)
require_once 'Conexion.php';
require_once 'Validacion.php'; // Incluye el nuevo archivo de validación

$mensaje_exito = '';
$errores = [];

// Inicializar la conexión a la base de datos
$db = new DB_Conexion();

// --- 1. LÓGICA DE PROCESAMIENTO Y VALIDACIÓN ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1.1. Llamada a la función de validación externa
    $resultado_validacion = validar_formulario($_POST, $db);
    
    $errores = $resultado_validacion['errores'];
    $datos_limpios = $resultado_validacion['datos_limpios'];
    
    // Extraer datos limpios a variables locales para la inserción
    extract($datos_limpios); // Esto crea variables como $nombre, $apellido, $edad, etc.

    // 1.2. Validaciones adicionales de la fecha 
    // El campo de fecha viene en $datos_limpios, se extrae como $fecha_formulario
    if (empty($fecha_formulario)) { 
        $errores[] = "Debe seleccionar una fecha para el formulario."; 
    }
    
    // 1.3. Inserción en la Base de Datos si no hay errores
    if (empty($errores)) {
        
        // Formateo de nombres y apellidos (Rubro 16)
        $nombre_db = ucwords(strtolower($nombre));
        $apellido_db = ucwords(strtolower($apellido));
        
        // Consulta preparada para mayor seguridad. AHORA INCLUYE 'fecha_formulario'
        $sql_inscriptor = $db->get_conexion()->prepare("
            INSERT INTO inscriptores (nombre, apellido, edad, sexo, id_pais_residencia, nacionalidad, correo, celular, observaciones, fecha_formulario) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Parámetros: s=string, i=integer. Total: 10 variables.
        // Cadena de tipos: Nombre(s), Apellido(s), Edad(i), Sexo(s), Pais_ID(i), Nacionalidad(s), Correo(s), Celular(s), Observaciones(s), Fecha(s)
        $sql_inscriptor->bind_param("ssisisssss", 
            $nombre_db, 
            $apellido_db, 
            $edad, 
            $sexo, 
            $id_pais_residencia, 
            $nacionalidad, 
            $correo, 
            $celular, 
            $observaciones,
            $fecha_formulario // ¡NUEVO CAMPO DE FECHA!
        );

        if ($sql_inscriptor->execute()) {
            $id_inscriptor_insertado = $sql_inscriptor->insert_id;
            
            // Inserción de Áreas de Interés
            if (!empty($temas_seleccionados)) {
                $sql_areas = "INSERT INTO inscriptor_areas (id_inscriptor, id_area) VALUES ";
                $valores = [];
                
                foreach ($temas_seleccionados as $id_area) {
                    $id_area_int = (int) $id_area; 
                    $valores[] = "({$id_inscriptor_insertado}, {$id_area_int})";
                }
                
                $sql_areas .= implode(", ", $valores);
                $db->ejecutar_consulta($sql_areas);
            }
            
            $mensaje_exito = "¡Datos guardados con éxito! Ahora puedes ver el reporte";
            
            // Limpia los campos del formulario después del éxito
            $_POST = [];
            
        } else {
            $errores[] = "Error al guardar el inscriptor: " . $db->get_conexion()->error;
        }
    }
}

// --- 2. CONSULTAS PARA POBLAR CAMPOS ---
$db_get = new DB_Conexion();

$paises_res = $db_get->obtener_datos("SELECT id_pais, nombre_pais FROM paises ORDER BY nombre_pais");
$areas_res = $db_get->obtener_datos("SELECT id_area, nombre_area FROM areas_interes ORDER BY nombre_area");

$db_get->cerrar_conexion(); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcial - Formulario de Datos</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>
    <div class="header-container">
        <img src="logo_izq.png" alt="Logo Izquierdo" class="logo izquierda">
        <img src="logo_derecho.png" alt="Logo Derecho" class="logo derecha">
    </div>

    <div class="contenedor">
        <h2>Registro de Interesados en Evento Tecnológico</h2>
        <!-- Se eliminó la fecha fija. Ahora está en un campo seleccionable dentro del formulario. -->

        <?php 
        // ... (Sección de mostrar errores/éxito) ...
        if (!empty($errores)) {
            echo '<div class="error">';
            echo '<strong>Se encontraron los siguientes errores:</strong><ul>';
            foreach ($errores as $error) {
                echo "<li>{$error}</li>";
            }
            echo '</ul></div>';
        }
        if (!empty($mensaje_exito)) {
            echo '<div class="exito">' . $mensaje_exito . '</div>';
        }
        ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

            <div class="grupo-control"><label for="nombre">Nombre:</label><input type="text" id="nombre" name="nombre" required value="<?php echo $_POST['nombre'] ?? ''; ?>"></div>
            <div class="grupo-control"><label for="apellido">Apellido:</label><input type="text" id="apellido" name="apellido" required value="<?php echo $_POST['apellido'] ?? ''; ?>"></div>
            <div class="grupo-control"><label for="edad">Edad:</label><input type="number" id="edad" name="edad" min="1" required value="<?php echo $_POST['edad'] ?? ''; ?>"></div>

            <!-- Campo de Fecha del Formulario (Seleccionable por el usuario) (Rubro 9) -->
            <div class="grupo-control">
                <label for="fecha_formulario">Fecha del Formulario:</label>
                <input type="date" id="fecha_formulario" name="fecha_formulario" required 
                       value="<?php echo $_POST['fecha_formulario'] ?? date('Y-m-d'); ?>">
            </div>

            <div class="grupo-control">
                <label>Sexo:</label>
                <div class="grupo-radio-checkbox">
                    <input type="radio" id="masculino" name="sexo" value="Masculino" required <?php echo (($_POST['sexo'] ?? '') == 'Masculino') ? 'checked' : ''; ?>> <label for="masculino">Masculino</label>
                    <input type="radio" id="femenino" name="sexo" value="Femenino" <?php echo (($_POST['sexo'] ?? '') == 'Femenino') ? 'checked' : ''; ?>> <label for="femenino">Femenino</label>
                    <input type="radio" id="otro" name="sexo" value="Otro" <?php echo (($_POST['sexo'] ?? '') == 'Otro') ? 'checked' : ''; ?>> <label for="otro">Otro</label>
                </div>
            </div>

            <div class="grupo-control">
                <label for="pais_residencia">País de Residencia:</label>
                <select id="pais_residencia" name="pais_residencia" required>
                    <option value="">-- Seleccione un país --</option>
                    <?php
                    if ($paises_res && $paises_res->num_rows > 0) {
                        while($pais = $paises_res->fetch_assoc()) {
                            $selected = (($_POST['pais_residencia'] ?? '') == $pais['id_pais']) ? 'selected' : '';
                            echo "<option value='{$pais['id_pais']}' {$selected}>{$pais['nombre_pais']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="grupo-control"><label for="nacionalidad">Nacionalidad:</label><input type="text" id="nacionalidad" name="nacionalidad" required value="<?php echo $_POST['nacionalidad'] ?? ''; ?>"></div>
            
            <div class="grupo-control">
                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo" required 
                        placeholder="ejemplo@dominio.com"
                        value="<?php echo $_POST['correo'] ?? ''; ?>">
            </div>

            <div class="grupo-control">
                <label for="celular">Celular:</label>
                <input type="tel" id="celular" name="celular" required 
                        placeholder="+507 6666-5555"
                        value="<?php echo $_POST['celular'] ?? ''; ?>">
            </div>

            <div class="grupo-control">
                <label>Tema Tecnológico que le gustaría aprender:</label>
                <div class="grupo-radio-checkbox">
                    <?php
                    // Mantiene los checkboxes seleccionados en caso de error
                    $temas_post = $_POST['temas'] ?? []; 
                    if ($areas_res && $areas_res->num_rows > 0) {
                        while($area = $areas_res->fetch_assoc()) {
                            $checked = in_array($area['id_area'], $temas_post) ? 'checked' : '';
                            echo "<input type='checkbox' id='tema_{$area['id_area']}' name='temas[]' value='{$area['id_area']}' {$checked}>";
                            echo "<label for='tema_{$area['id_area']}'>{$area['nombre_area']}</label><br>";
                        }
                    }
                    ?>
                </div>
            </div>
            
            <div class="grupo-control">
                <label for="observaciones">Observaciones o Consulta:</label>
                <textarea id="observaciones" name="observaciones" rows="4"><?php echo $_POST['observaciones'] ?? ''; ?></textarea>
            </div>

            <button type="submit">Registrar Datos</button>
            <a href="reporte.php" class="enlace-reporte">Ver Reporte de Datos Guardados</a>

        </form>
    </div>

    <footer>
        <p>&copy; 2025 iTECH. All rights reserved.</p>
    </footer>
</body>
</html>