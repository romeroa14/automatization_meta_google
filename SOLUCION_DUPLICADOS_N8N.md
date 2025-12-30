# 🔧 Solución para Duplicados en n8n

## 🔍 Problema Identificado

n8n está insertando registros duplicados en la tabla `conversations` porque:
1. **No hay restricción única** en `message_id` en la base de datos
2. El nodo "Insert a record Conversations" se ejecuta dos veces o hay múltiples rutas que llegan al mismo nodo

## ✅ Solución 1: Agregar Restricción Única en Base de Datos

### Paso 1: Ejecutar la migración

```bash
cd /var/www/html/automatization_meta_google
docker exec -it laravel-php php artisan migrate
```

Esta migración:
- ✅ Elimina duplicados existentes (mantiene el más reciente)
- ✅ Agrega un índice único en `message_id` para prevenir futuros duplicados

### Paso 2: Verificar que funcionó

```bash
docker exec -it laravel-php php artisan tinker
```

```php
// Verificar que no hay duplicados
DB::table('conversations')
    ->select('message_id', DB::raw('COUNT(*) as count'))
    ->whereNotNull('message_id')
    ->groupBy('message_id')
    ->having('count', '>', 1)
    ->get();
// Debe retornar vacío si no hay duplicados
```

## ✅ Solución 2: Configurar n8n Correctamente

### Opción A: Usar "Skip on Conflict" (Recomendado)

En el nodo **"Insert a record Conversations"**:

1. **Habilitar "Skip on Conflict"** ✅ (ya lo tienes activado)
2. **Configurar "Columns to match on"**:
   ```
   message_id
   ```
3. **Verificar que el workflow no tenga loops**:
   - Asegúrate de que solo hay UNA ruta que llega al nodo "Insert a record Conversations"
   - Si hay múltiples rutas, usa un nodo "Merge" antes del insert

### Opción B: Verificar antes de Insertar

Agregar un nodo **"Search Records Conversations"** ANTES del "Insert":

1. **Nodo: "Search Records Conversations"**
   - **Table**: `conversations`
   - **Filter**: `message_id = {{ $('Parse Incoming').item.json.messageId }}`
   - **Return All**: `false`
   - **Limit**: `1`

2. **Nodo: "IF" (condicional)**
   - **Condition**: `{{ $json.length === 0 }}` (si no existe)
   - **True**: Conectar a "Insert a record Conversations"
   - **False**: No hacer nada (skip)

### Opción C: Usar "Insert or Update" en lugar de "Insert"

En el nodo **"Insert a record Conversations"**:

1. **Operation**: Cambiar de `Insert` a `Insert or Update`
2. **Columns to match on**: `message_id`
3. **Values to Send**: Mapear todos los campos normalmente

Esto actualizará el registro si existe, o lo creará si no existe.

## 🔍 Verificar el Workflow en n8n

### Checklist:

- [ ] Solo hay UNA ruta que llega a "Insert a record Conversations"
- [ ] "Skip on Conflict" está habilitado
- [ ] "Columns to match on" incluye `message_id`
- [ ] No hay loops en el workflow
- [ ] El webhook trigger no se ejecuta dos veces

### Cómo verificar si hay múltiples rutas:

1. Abre el workflow en n8n
2. Busca el nodo "Insert a record Conversations"
3. Verifica cuántas flechas (conexiones) ENTRAN a ese nodo
4. Si hay más de una, usa un nodo "Merge" para unificarlas

## 🚀 Después de Aplicar las Soluciones

1. **Ejecutar la migración** (Solución 1)
2. **Configurar n8n** (Solución 2 - Opción A, B o C)
3. **Probar el flujo**:
   - Enviar un mensaje desde WhatsApp
   - Verificar en la base de datos que solo se crea UN registro
   - Verificar que n8n no muestra errores de duplicados

## 📊 Verificar Duplicados en Base de Datos

```sql
-- Ver duplicados por message_id
SELECT message_id, COUNT(*) as count
FROM conversations
WHERE message_id IS NOT NULL
GROUP BY message_id
HAVING COUNT(*) > 1;

-- Eliminar duplicados manualmente (mantener el más reciente)
DELETE FROM conversations c1
WHERE EXISTS (
    SELECT 1 FROM conversations c2
    WHERE c2.message_id = c1.message_id
    AND c2.message_id IS NOT NULL
    AND c2.id > c1.id
);
```

## ⚠️ Nota Importante

Después de agregar la restricción única, si n8n intenta insertar un duplicado:
- **Con "Skip on Conflict"**: n8n simplemente saltará el insert sin error
- **Sin "Skip on Conflict"**: n8n mostrará un error de constraint violation

Por eso es importante tener "Skip on Conflict" habilitado.

