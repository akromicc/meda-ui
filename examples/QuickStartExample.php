<?php

/**
 * 🚀 QUICK START - Crear Aplicación CRUD en 5 Minutos
 * 
 * Este ejemplo muestra cómo crear una aplicación completa con Dynamic UI
 * usando el nuevo sistema automático en solo 5 pasos.
 */

/*
|--------------------------------------------------------------------------
| PASO 1: UNA SOLA RUTA (30 segundos)
|--------------------------------------------------------------------------
| 
| Agregar esta línea en routes/api.php
|
*/

// routes/api.php
Route::any('/dynamic/{model}/{action?}/{id?}', [\Meda\DynamicUI\Http\Controllers\AutoDynamicController::class, 'handle']);

/*
¡YA ESTÁ! Esta ruta maneja automáticamente:
- GET /dynamic/users (listar)
- POST /dynamic/users (crear)  
- GET /dynamic/users/123 (mostrar)
- PUT /dynamic/users/123 (actualizar)
- DELETE /dynamic/users/123 (eliminar)
- GET /dynamic/users/metadata (metadatos)
*/

/*
|--------------------------------------------------------------------------
| PASO 2: CREAR MODELO BÁSICO (1 minuto)
|--------------------------------------------------------------------------
*/

// app/Models/Task.php
/*
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Meda\DynamicUI\Traits\HasMetadata;

class Task extends Model
{
    use HasMetadata;

    protected $fillable = [
        'title',
        'description', 
        'priority',
        'is_completed',
        'due_date'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date'
    ];
}
*/

/*
¡YA TIENES UNA API COMPLETA!

✅ GET /dynamic/tasks - Lista todas las tareas
✅ POST /dynamic/tasks - Crea nueva tarea
✅ GET /dynamic/tasks/1 - Muestra tarea 1
✅ PUT /dynamic/tasks/1 - Actualiza tarea 1
✅ DELETE /dynamic/tasks/1 - Elimina tarea 1
✅ GET /dynamic/tasks/metadata - Metadatos de la tabla/modal
*/

/*
|--------------------------------------------------------------------------
| PASO 3: MIGRACIÓN RÁPIDA (1 minuto)
|--------------------------------------------------------------------------
*/

// database/migrations/xxxx_create_tasks_table.php
/*
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('is_completed')->default(false);
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
*/

/*
|--------------------------------------------------------------------------
| PASO 4: VISTA FRONTEND UNIVERSAL (2 minutos)
|--------------------------------------------------------------------------
*/

// resources/js/views/TasksView.vue
/*
<template>
  <div class="p-6">
    <DynamicView 
      model-name="tasks"
      :use-auto-routes="true"
      base-path="/dynamic"
    >
      <!-- Personalización opcional de celdas -->
      <template #cell-priority="{ value }">
        <span 
          :class="{
            'bg-red-100 text-red-800': value === 'high',
            'bg-yellow-100 text-yellow-800': value === 'medium', 
            'bg-green-100 text-green-800': value === 'low'
          }"
          class="px-2 py-1 rounded-full text-xs font-medium"
        >
          {{ value.toUpperCase() }}
        </span>
      </template>

      <template #cell-is_completed="{ value }">
        <span 
          :class="value ? 'text-green-600' : 'text-gray-400'"
          class="text-sm font-medium"
        >
          {{ value ? '✅ Completada' : '⏳ Pendiente' }}
        </span>
      </template>
    </DynamicView>
  </div>
</template>

<script>
import { DynamicView } from '@meda/dynamic-ui'

export default {
  name: 'TasksView',
  components: { DynamicView }
}
</script>
*/

/*
|--------------------------------------------------------------------------
| PASO 5: PERSONALIZACIÓN OPCIONAL (1 minuto)
|--------------------------------------------------------------------------
| 
| Si quieres personalizar la UI, solo agrega esto al modelo:
|
*/

// Actualizar app/Models/Task.php
/*
class Task extends Model
{
    use HasMetadata;
    
    // ... fillable y casts ...

    // Personalizar modales
    public function defineModals(): array
    {
        return [
            'create' => [
                'label' => 'Nueva Tarea',
                'icon' => 'fa fa-plus',
                'fields' => [
                    [
                        'key' => 'title',
                        'label' => 'Título de la Tarea',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'Ej: Completar proyecto'
                    ],
                    [
                        'key' => 'description', 
                        'label' => 'Descripción',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Describe la tarea...'
                    ],
                    [
                        'key' => 'priority',
                        'label' => 'Prioridad',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            ['value' => 'low', 'label' => 'Baja'],
                            ['value' => 'medium', 'label' => 'Media'],
                            ['value' => 'high', 'label' => 'Alta']
                        ]
                    ],
                    [
                        'key' => 'due_date',
                        'label' => 'Fecha Límite',
                        'type' => 'date',
                        'required' => false
                    ],
                    [
                        'key' => 'is_completed',
                        'label' => 'Completada',
                        'type' => 'boolean',
                        'required' => false,
                        'checkboxLabel' => 'Marcar como completada'
                    ]
                ]
            ],
            'view' => ['label' => 'Ver Tarea'],
            'edit' => ['label' => 'Editar Tarea'],
            'delete' => [
                'label' => 'Eliminar Tarea',
                'confirmMessage' => '¿Estás seguro de eliminar esta tarea?'
            ]
        ];
    }

    // Acciones personalizadas
    public function defineCustomActions(): array
    {
        return [
            'toggle_completion' => [
                'label' => 'Cambiar Estado',
                'icon' => 'fa fa-check-circle',
                'color' => 'success',
                'type' => 'confirm',
                'confirmMessage' => '¿Cambiar el estado de completado?'
            ],
            'duplicate' => [
                'label' => 'Duplicar Tarea',
                'icon' => 'fa fa-copy',
                'color' => 'secondary',
                'type' => 'confirm'
            ]
        ];
    }

    // Acción personalizada en el modelo
    public function handleToggleCompletion()
    {
        $this->update(['is_completed' => !$this->is_completed]);
        return $this;
    }
}
*/

/*
|--------------------------------------------------------------------------
| ¡RESULTADO FINAL! 🎉
|--------------------------------------------------------------------------
|
| Con estos 5 pasos tienes:
|
| ✅ API REST completa con validación
| ✅ Interfaz web con tabla, filtros, búsqueda
| ✅ Modales para crear, editar, ver, eliminar
| ✅ Acciones personalizadas
| ✅ Paginación automática
| ✅ Ordenamiento por columnas
| ✅ Diseño responsive
| ✅ Modo oscuro incluido
|
| TODO ESTO EN 5 MINUTOS! 🚀
|
| Para agregar más entidades:
| 1. Crear modelo con HasMetadata
| 2. Crear migración 
| 3. ¡Ya funciona automáticamente!
|
| Para personalizar:
| - defineModals() en el modelo
| - defineCustomActions() en el modelo
| - Slots personalizados en Vue
| - Middleware para permisos
|
|--------------------------------------------------------------------------
| EJEMPLOS ADICIONALES DE ENTIDADES
|--------------------------------------------------------------------------
*/

// Modelo de Producto (e-commerce)
/*
class Product extends Model
{
    use HasMetadata;
    
    protected $fillable = [
        'name', 'description', 'price', 'sku', 
        'category_id', 'is_active', 'stock'
    ];
    
    // Automáticamente genera CRUD completo para productos
    // URL: /dynamic/products
}
*/

// Modelo de Usuario
/*
class User extends Model  
{
    use HasMetadata;
    
    protected $fillable = [
        'name', 'email', 'role', 'is_active'
    ];
    
    // Automáticamente genera gestión de usuarios
    // URL: /dynamic/users
}
*/

// Modelo de Orden
/*
class Order extends Model
{
    use HasMetadata;
    
    protected $fillable = [
        'user_id', 'total', 'status', 'notes'
    ];
    
    // Automáticamente genera gestión de órdenes
    // URL: /dynamic/orders
}
*/

/*
|--------------------------------------------------------------------------
| MÚLTIPLES APLICACIONES CON UNA LIBRERÍA
|--------------------------------------------------------------------------
|
| Con Dynamic UI puedes crear:
|
| 🏪 E-commerce: productos, órdenes, clientes, categorías
| 👥 CRM: contactos, leads, empresas, actividades  
| 📋 Task Manager: tareas, proyectos, usuarios, equipos
| 🏥 Sistema Médico: pacientes, citas, doctores, historiales
| 🎓 Sistema Educativo: estudiantes, cursos, profesores, notas
| 📊 Dashboard Admin: usuarios, configuraciones, reportes
| 🏠 Real Estate: propiedades, clientes, agentes, citas
| 📱 SaaS Platform: suscripciones, facturas, usuarios, features
|
| ¡Todo con la misma base de código!
|
*/