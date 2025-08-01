# 📘 Especificación de Intercambio JSON – Juego Educativo

Este documento detalla todos los formatos de JSON que el **cliente puede enviar** y los que el **servidor puede devolver** en las distintas fases del juego.

---

## 🟢 1. Registro de jugador

### 📤 Cliente envía:
```json
{}
```

### 📥 Servidor responde:

#### Primer jugador:
```json
{
  "session_id": 10,
  "player_id": 25,
  "numero_jugador": 1,
  "status": "en espera"
}
```

#### Segundo jugador:
```json
{
  "session_id": 10,
  "player_id": 26,
  "numero_jugador": 2,
  "status": "en espera"
}
```

---

## 🔄 2. Polling de estado de sesión

### 📤 Cliente:
```json
{
  "session_id": 10,
  "player_id": 25,
  "numero_jugador": 1
}
```

### 📥 Servidor responde:

#### Si la sesión sigue en espera:
```json
{
  "status": "en espera",
  "message": "La sesión no está activa.",
  "session_id": 10,
  "player_id": 25,
  "numero_jugador": 1
}
```

#### Si la sesión está lista para jugar:
```json
{
  "session_id": 10,
  "board_id": 7,
  "player_id": 25,
  "numero_jugador": 1,
  "op1": 3,
  "op2": 6,
  "ex_num": 1,
  "puntaje": 0,
  "skips": 0,
  "rival": 0
}
```

---

## 🎮 3. Envío de respuestas o skip

### 📤 Cliente:
```json
{
  "session_id": 10,
  "player_id": 25,
  "skip": false,
  "skips": 1,
  "aciertos": 3,
  "rival": 2,
  "ex_num": 4,
  "res": 36
}
```

### 📤 Cliente si pide skip:
```json
{
  "session_id": 10,
  "player_id": 25,
  "skip": true,
  "skips": 1,
  "aciertos": 3,
  "rival": 2,
  "ex_num": 4,
  "res": 0
}
```

### 📥 Servidor responde:

#### Respuesta correcta o skip válido:
```json
{
  "session_id": 10,
  "board_id": 7,
  "player_id": 25,
  "numero_jugador": 1,
  "op1": 2,
  "op2": 5,
  "ex_num": 5,
  "puntaje": 4,
  "skips": 1,
  "rival": 2
}
```

#### Respuesta incorrecta (misma pregunta):
```json
{
  "session_id": 10,
  "board_id": 7,
  "player_id": 25,
  "numero_jugador": 1,
  "op1": 2,
  "op2": 5,
  "ex_num": 4,
  "puntaje": 3,
  "skips": 1,
  "rival": 2
}
```

#### Si ya hizo 3 skips (se repite la pregunta):
```json
{
  "session_id": 10,
  "board_id": 7,
  "player_id": 25,
  "numero_jugador": 1,
  "op1": 2,
  "op2": 5,
  "ex_num": 4,
  "puntaje": 3,
  "skips": 3,
  "rival": 2
}
```

---

## 🏁 4. Final de partida

### 📥 Si gana el jugador:
```json
{
  "ganador": true,
  "mensaje": "¡Has ganado!",
  "puntaje": 10
}
```

### 📥 Si el rival ya ha ganado:
```json
{
  "ganador": false,
  "mensaje": "Has perdido.",
  "puntaje": 6
}
```

---

## ❌ 5. Posibles errores

### Jugador no existe:
```json
{
  "error": "Jugador no encontrado en la sesión",
  "session_id": 10,
  "player_id": 999,
  "players_disponibles": [25, 26]
}
```

### Ejercicio desincronizado:
```json
{
  "error": "Número de ejercicio desincronizado",
  "esperado": 5,
  "recibido": 4,
  "session_id": 10,
  "board_id": 7,
  "numero_jugador": 1,
  "player_id": 25
}
```

### Puntaje desincronizado:
```json
{
  "error": "Puntaje desincronizado",
  "esperado": 4,
  "recibido": 3,
  "session_id": 10,
  "board_id": 7,
  "numero_jugador": 1,
  "player_id": 25
}
```

---

## 📌 Notas finales

- El campo `ex_num` va de 1 a 13 y representa la posición en el tablero.
- El cliente debe respetar la sincronización de `puntaje` y `ex_num`.
- Si el juego ya terminó (estado `"finalizada"`), cualquier interacción devolverá si ha ganado o perdido.