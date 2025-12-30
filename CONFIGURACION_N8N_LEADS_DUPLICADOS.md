# 🔧 Solución: Evitar Duplicados de Leads en n8n

## 🔍 Problema

n8n está creando leads duplicados cuando busca por `phone_number` y encuentra múltiples registros, o cuando inserta directamente sin verificar si existe.

## ⚠️ ERROR: "there is no unique or exclusion constraint matching the ON CONFLICT specification"

Este error ocurre cuando n8n intenta usar "Insert or Update" pero la tabla `leads` no tiene un índice único en `phone_number`.

### Solución: Ejecutar la migración

```bash
php artisan migrate
```

O ejecutar la migración específica:

```bash
php artisan migrate --path=database/migrations/2025_12_30_003000_add_unique_constraint_to_leads_phone_number.php
```

Esta migración:
- ✅ Elimina duplicados existentes (mantiene el más reciente)
- ✅ Agrega un índice único en `phone_number` para prevenir futuros duplicados

## ✅ Solución: Configurar "Insert or Update" en n8n

### Paso 1: Configurar el nodo "Inserts Records leads" o "Insert/Update Records leads"

En n8n, el nodo que inserta leads debe usar **"Insert or Update"** en lugar de solo **"Insert"**.

#### Configuración del nodo:

1. **Operation**: Cambiar de `Insert` a `Insert or Update`
2. **Columns to match on**: `phone_number`
3. **Values to Send**: Mapear todos los campos normalmente:
   - `phone_number`: `{{ $json.fromNumber }}` o `{{ $('Parse Incoming').item.json.fromNumber }}`
   - `client_name`: `{{ $json.profileName }}`
   - `user_id`: `1` (o el user_id correcto)
   - `intent`: `{{ $json.intent }}` (si viene de n8n)
   - `lead_level`: `{{ $json.leadLevel }}` (si viene de n8n)
   - `stage`: `{{ $json.stage }}` (si viene de n8n)
   - `confidence_score`: `{{ $json.confidence }}` (si viene de n8n)

### Paso 2: Verificar que solo hay UN nodo que inserta leads

Asegúrate de que solo hay **UN** nodo en el workflow que inserta/actualiza leads. Si hay múltiples nodos, elimina los duplicados o usa un nodo "Merge" antes del insert.

### Paso 3: Verificar el orden del workflow

El flujo debería ser:
```
Webhook Trigger
  ↓
Parse Incoming
  ↓
Search Records Leads (buscar por phone_number)
  ↓
IF (existe lead)
  ↓
  ├─→ Update Records leads (actualizar lead existente)
  └─→ Insert Records leads (crear nuevo lead)
```

O mejor aún, usar directamente:
```
Webhook Trigger
  ↓
Parse Incoming
  ↓
Insert or Update Records leads (con phone_number como match)
```

## 🔍 Verificar en n8n

### Checklist:

- [ ] El nodo usa **"Insert or Update"** (no solo "Insert")
- [ ] **"Columns to match on"** incluye `phone_number`
- [ ] Solo hay **UN** nodo que inserta/actualiza leads
- [ ] El `phone_number` se mapea correctamente desde el webhook

### Cómo verificar si hay duplicados:

En n8n, después de ejecutar el workflow, verifica en el nodo "Search Records Leads":
- Si encuentra **más de 1 registro** con el mismo `phone_number`, hay un problema
- Debe encontrar **0 o 1 registro** máximo

## 🚀 Solución Alternativa: Usar "Check if exists" antes de Insert

Si no puedes usar "Insert or Update", puedes usar esta estructura:

1. **Nodo: "Search Records Leads"**
   - Filter: `phone_number = {{ $json.fromNumber }}`
   - Return All: `false`
   - Limit: `1`

2. **Nodo: "IF" (condicional)**
   - Condition: `{{ $json.length === 0 }}` (si no existe)
   - **True**: Conectar a "Insert Records leads"
   - **False**: Conectar a "Update Records leads" (usando el `id` encontrado)

## 📊 Verificar en Base de Datos

```sql
-- Ver duplicados por phone_number
SELECT phone_number, COUNT(*) as count
FROM leads
WHERE phone_number = '584242536795'
GROUP BY phone_number
HAVING COUNT(*) > 1;

-- Eliminar duplicados (mantener el más reciente)
DELETE FROM leads c1
WHERE EXISTS (
    SELECT 1 FROM leads c2
    WHERE c2.phone_number = c1.phone_number
    AND c2.id > c1.id
);
```

## ⚠️ Nota Importante

Después de configurar "Insert or Update" en n8n:
- Si el lead existe → se actualizará
- Si el lead no existe → se creará
- **NO se crearán duplicados**

