<?php

namespace Jekis\NewsService\Controller;

class NewsController
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    public function __construct(\Doctrine\DBAL\Connection $conn) {
        $this->conn = $conn;
    }


    public function get($id)
    {
        return $this->conn->fetchAssoc('SELECT * FROM news WHERE id = ?', array((int) $id));
    }

    public function push(array $data, $id = null)
    {
        // Filter data
        $allowedFields = array('title', 'body', 'created');
        $data = array_intersect_key($data, array_flip($allowedFields));

        if (empty($data)) {
            throw new \InvalidArgumentException('Updates are missing.');
        }

        if ($id) {
            // Update
            $affectedRows = $this->conn->update('news', $data, array('id' => $id));
        } else {
            // Insert
            $data += array(
                'created' => date('Y-m-d H:i:s'),
            );
            $affectedRows = $this->conn->insert('news', $data);
            $id = $this->conn->lastInsertId();
        }

        if ($affectedRows && $id) {
            return $this->get($id);
        }

        return null;
    }

    public function getList($limit = 10, $offset = 0, $sort = '-created', $startDate = null, $endDate = null)
    {
        $qb = $this->conn->createQueryBuilder()
            ->select('n.*')
            ->from('news', 'n')
            ->setMaxResults((int) $limit)
            ->setFirstResult((int) $offset)
        ;

        // Order by
        if (preg_match('~^-?(id|title|created)$~', $sort)) {
            if (strpos($sort, '-') === 0) {
                $qb->orderBy(substr($sort, 1), 'DESC');
            } else {
                $qb->orderBy($sort, 'ASC');
            }
        }

        // Range
        if ($startDate) {
            $date = date('Y-m-d H:i:s', strtotime($startDate));
            $qb->andWhere('n.created > :startDate')->setParameter('startDate', $date);
        }
        if ($endDate) {
            $date = date('Y-m-d H:i:s', strtotime($endDate));
            $qb->andWhere('n.created < :endDate')->setParameter('endDate', $date);
        }

        return $qb->execute()->fetchAll();
    }
}
