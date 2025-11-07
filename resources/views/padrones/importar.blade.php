<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Importar padrón</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h1>Importar padrón</h1>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <form action="{{ route('padrones.store') }}" method="POST" enctype="multipart/form-data" class="mb-4">
    @csrf

    <div class="row mb-3">
      <div class="col-md-3">
        <label class="form-label">Año</label>
        <input type="number" name="anio" class="form-control" required value="{{ old('anio', date('Y')) }}">
      </div>

      <div class="col-md-3">
        <label class="form-label">Facultad</label>
        <select name="id_facultad" class="form-control" required>
          <option value="">-- elegir --</option>
          @foreach($facultades as $f)
            <option value="{{ $f->id }}">{{ $f->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Claustro</label>
        <select name="id_claustro" class="form-control" required>
          <option value="">-- elegir --</option>
          @foreach($claustros as $c)
            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Sede (opcional)</label>
        <select name="id_sede" class="form-control">
          <option value="">-- ninguna --</option>
          @foreach($sedes as $s)
            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Archivo Excel (.xlsx, .xls o .csv)</label>
      <input type="file" name="archivo" accept=".xlsx,.xls,.csv" class="form-control" required>
      <div class="form-text">El archivo debe tener columnas: "Apellido y Nombre" (o "apellido" y "nombre"), "dni", "legajo". Si trae "sede", la usaremos.</div>
    </div>

    <button class="btn btn-primary">Subir e importar</button>
    <a href="{{ route('padrones.index') }}" class="btn btn-secondary">Volver</a>
  </form>

  <hr>
  <p>Ejemplo de formato esperado en la primera fila del Excel (cabeceras):</p>
  <pre>
Apellido y Nombre , dni , legajo , sede
Pérez, Juan       , 12345678 , AA2222 , Neuquén
Gómez, María      , 23456789 , BB3344 , Cipolletti
  </pre>

</body>
</html>
