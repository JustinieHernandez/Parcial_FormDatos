<?php
// Validacion.php



function limpiar_dato(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


function validar_formulario(array $post_data, DB_Conexion $db_conexion): array {
    $errores = [];
    $datos_limpios = [];
    
    // RegEx para solo letras (incluye acentos, ñ y espacios)
    $regex_letras = '/^[\p{L}áéíóúÁÉÍÓÚñÑ\s]+$/u';
    
    // --- 1. RECOLECCIÓN Y LIMPIEZA DE DATOS ---
    $datos_limpios['nombre'] = limpiar_dato($post_data['nombre'] ?? '');
    $datos_limpios['apellido'] = limpiar_dato($post_data['apellido'] ?? '');
    $datos_limpios['edad'] = limpiar_dato($post_data['edad'] ?? '');
    $datos_limpios['sexo'] = limpiar_dato($post_data['sexo'] ?? '');
    // id_pais_residencia se limpia como string, se valida como integer
    $datos_limpios['id_pais_residencia'] = limpiar_dato($post_data['pais_residencia'] ?? ''); 
    $datos_limpios['nacionalidad'] = limpiar_dato($post_data['nacionalidad'] ?? '');
    $datos_limpios['correo'] = limpiar_dato($post_data['correo'] ?? '');
    $datos_limpios['celular'] = limpiar_dato($post_data['celular'] ?? '');
    $datos_limpios['observaciones'] = limpiar_dato($post_data['observaciones'] ?? '');
    $datos_limpios['temas_seleccionados'] = $post_data['temas'] ?? [];
    // NUEVO: FECHA DEL FORMULARIO
    $datos_limpios['fecha_formulario'] = limpiar_dato($post_data['fecha_formulario'] ?? '');


    // --- 2. VALIDACIÓN DE PRESENCIA Y FORMATO (Rubro 15) ---

    // Nombre y Apellido
    if (empty($datos_limpios['nombre'])) {
        $errores[] = "El Nombre es obligatorio.";
    } elseif (!preg_match($regex_letras, $datos_limpios['nombre'])) {
        $errores[] = "El Nombre solo debe contener letras y espacios.";
    }

    if (empty($datos_limpios['apellido'])) {
        $errores[] = "El Apellido es obligatorio.";
    } elseif (!preg_match($regex_letras, $datos_limpios['apellido'])) {
        $errores[] = "El Apellido solo debe contener letras y espacios.";
    }
    
    // Edad
    if (empty($datos_limpios['edad'])) {
        $errores[] = "La Edad es obligatoria.";
    } elseif (!filter_var($datos_limpios['edad'], FILTER_VALIDATE_INT) || $datos_limpios['edad'] < 1) {
        $errores[] = "La Edad debe ser un número entero positivo.";
    } else {
        $datos_limpios['edad'] = (int) $datos_limpios['edad']; // Convertir a integer para la BD
    }

    // Sexo
    if (empty($datos_limpios['sexo'])) {
        $errores[] = "Debe seleccionar un Sexo.";
    } elseif (!in_array($datos_limpios['sexo'], ['Masculino', 'Femenino', 'Otro'])) {
        $errores[] = "El valor de Sexo no es válido.";
    }

    // País de Residencia
    if (empty($datos_limpios['id_pais_residencia'])) {
        $errores[] = "El País de Residencia es obligatorio.";
    } elseif (!filter_var($datos_limpios['id_pais_residencia'], FILTER_VALIDATE_INT)) {
        $errores[] = "El País de Residencia seleccionado no es válido.";
    } else {
        $datos_limpios['id_pais_residencia'] = (int) $datos_limpios['id_pais_residencia'];
    }

    // Nacionalidad
    if (empty($datos_limpios['nacionalidad'])) {
        $errores[] = "La Nacionalidad es obligatoria.";
    } elseif (!preg_match($regex_letras, $datos_limpios['nacionalidad'])) {
        $errores[] = "La Nacionalidad solo debe contener letras y espacios.";
    }

    // Correo
    if (empty($datos_limpios['correo'])) {
        $errores[] = "El Correo es obligatorio.";
    } elseif (!filter_var($datos_limpios['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El formato del Correo no es válido.";
    } else {
        // Validación de duplicidad de correo
        $consulta = $db_conexion->ejecutar_consulta_con_param("SELECT id_inscriptor FROM inscriptores WHERE correo = ?", "s", [$datos_limpios['correo']]);
        if ($consulta && $consulta->num_rows > 0) {
            $errores[] = "El Correo ya se encuentra registrado.";
        }
    }

    // Celular (Validación de formato y longitud)
    if (empty($datos_limpios['celular'])) {
        $errores[] = "El Celular es obligatorio.";
    } else {
        // Expresión Regular para números de teléfono flexibles:
        // Permite opcionalmente + al inicio, dígitos, espacios y guiones.
        // Ej: +507 6666-5555, 66665555, 666-5555
        $regex_celular = '/^\+?[\d\s-]{8,15}$/'; 
        
        if (!preg_match($regex_celular, $datos_limpios['celular'])) {
            $errores[] = "El formato del Celular no es válido. Debe tener entre 8 y 15 dígitos/caracteres (ej: +507 6666-5555).";
        }

        // Validación de duplicidad de celular
        if (empty($errores)) { // Ejecutar duplicidad solo si no hay error de formato
            $consulta = $db_conexion->ejecutar_consulta_con_param("SELECT id_inscriptor FROM inscriptores WHERE celular = ?", "s", [$datos_limpios['celular']]);
            if ($consulta && $consulta->num_rows > 0) {
                $errores[] = "El Número de Celular ya se encuentra registrado.";
            }
        }
    }
    
    // Temas (Mínimo uno obligatorio)
    if (empty($datos_limpios['temas_seleccionados'])) {
        $errores[] = "Debe seleccionar al menos un Tema Tecnológico.";
    }



    return [
        'errores' => $errores,
        'datos_limpios' => $datos_limpios
    ];
}