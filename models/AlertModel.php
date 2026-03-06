<?php
class AlertModel {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function getAll($active_only = false) {
        $sql = "SELECT * FROM alerts" . ($active_only ? " WHERE is_active = 1" : "") . " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO alerts (type, title, body, source, affected_area, is_active) VALUES (?,?,?,?,?,1)");
        return $stmt->execute([$data['type'], $data['title'], $data['body'], $data['source'] ?? '', $data['affected_area'] ?? '']);
    }

    public function update($id, $data) {
        $fields = []; $values = [];
        foreach (['type','title','body','source','affected_area','is_active'] as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "$f=?"; $values[] = $data[$f]; }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE alerts SET " . implode(',',$fields) . " WHERE alert_id=?");
        return $stmt->execute($values);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM alerts WHERE alert_id=?");
        return $stmt->execute([$id]);
    }

    public function getCounts() {
        $stmt = $this->db->prepare("SELECT type, COUNT(*) as cnt FROM alerts WHERE is_active=1 GROUP BY type");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = ['critical'=>0,'warning'=>0,'info'=>0];
        foreach ($rows as $r) if (isset($counts[$r['type']])) $counts[$r['type']] = (int)$r['cnt'];
        return $counts;
    }
    /**
     * humanTimeDiff — returns a human-readable time difference string.
     * Previously a standalone function in views/shelter/alerts.php.
     */
    public static function humanTimeDiff($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)      return 'Just now';
        if ($diff < 3600)    return floor($diff / 60)  . ' min ago';
        if ($diff < 86400)   return floor($diff / 3600) . ' hours ago';
        return floor($diff / 86400) . ' days ago';
    }
}
