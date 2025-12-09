<?php
require_once 'database.php';

class Guerrero
{
    private PDO $db;

    public function __construct()
    {
        $this->db = conectarDb();
    }

    public function todos($buscar = '', $raza = '', $pagina = 1)
    {
        $where = "WHERE 1=1";
        $params = [];
        if ($buscar) {
            $where .= " AND nombre LIKE :buscar";
            $params[':buscar'] = "%$buscar%";
        }
        if ($raza) {
            $where .= " AND raza = :raza";
            $params[':raza'] = $raza;
        }

        $porPagina = 7;
        $offset = ($pagina - 1) * $porPagina;

        $sql = "SELECT * FROM guerreros $where ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function total($buscar = '', $raza = '')
    {
        $where = "WHERE 1=1";
        $params = [];
        if ($buscar) {
            $where .= " AND nombre LIKE :buscar";
            $params[':buscar'] = "%$buscar%";
        }
        if ($raza) {
            $where .= " AND raza = :raza";
            $params[':raza'] = $raza;
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM guerreros $where");
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function razas()
    {
        return $this->db->query("SELECT DISTINCT raza FROM guerreros ORDER BY raza")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function crear($datos)
    {
        $sql = "INSERT INTO guerreros (nombre, raza, descripcion, imagen, poder) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['nombre'],
            $datos['raza'],
            $datos['descripcion'],
            $datos['imagen'] ?? '',
            $datos['poder'] ?? 'Desconocido'
        ]);
        return $this->db->lastInsertId();
    }

    public function actualizar($id, $datos)
    {
        $sql = "UPDATE guerreros SET nombre=?, raza=?, descripcion=?, imagen=?, poder=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $datos['nombre'],
            $datos['raza'],
            $datos['descripcion'],
            $datos['imagen'] ?? '',
            $datos['poder'] ?? 'Desconocido',
            $id
        ]);
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM guerreros WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function uno($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM guerreros WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
