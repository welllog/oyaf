<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/3/14
 * Time: 下午2:45
 */

namespace Odb;


class TestSqlBuilder
{
    public static function getSqlBuilder()
    {
        return new SqlBuilder('pdo', 'test_');
    }

    public static function testTable()
    {
        $i = self::getSqlBuilder();
        foreach ([' user', ' user  as   u ', ' user   u '] as $t) {
            $i->table($t);
            $r = $i->getSql('table');
            echo $r.'<br>'.PHP_EOL;
        }
    }

    public static function testSelect()
    {
        $i = self::getSqlBuilder();
        foreach ([['name', 'u.age', 'count(u.age)', 'count(u . age) as num'], [
            ['count(u. age)  num', 'count(age) num ', 'count(* ) ', ' count(  u . * )']
        ]] as $t) {
            $i->select(...$t);
            $r = $i->getSql('columns');
            echo $r.'<br>'.PHP_EOL;
        }
    }

    public static function testWhere()
    {
        $i = self::getSqlBuilder();
        $sql = $i->table('user')->where('id', 2)->where([['u.name', 'ctr'], ['u.age', '=', 5]])
            ->orWhere('score', '>', 6)->orWhere(function($query){
                $query->whereRaw('test_b.address = ?', ['asds'])->whereNull('c.title')
                ->whereBetween('d', [5,6])
                ->whereIn('name', ['li', 'chen', 'zs'])
                ->whereColumn(' c . age ', '>', 'b.age');
            })->getSql();
            echo $sql.'<br>'.PHP_EOL;

    }

    public static function testJoin()
    {
        $i = self::getSqlBuilder();
        $sql = $i->table('  user  u  ')->join(' article  as a', 'a.uid', '=', 'u.id')
            ->leftJoin('score    s  ', 's.aid', '=', ' a . id ')
            ->rightJoin('comment as c', 'test_c.sid=test_s.id and c.comments > ?', [6])
            ->getSql();
        echo $sql.'<br>'.PHP_EOL;
    }

    public static function testRest()
    {
        $i = self::getSqlBuilder();
        $sql = $i->table('  user  u  ')
            ->orderBy('id', 'desc')->orderBy('age', 'asc')
            ->groupBy('team', 'age')
            ->having('age', '>', 3)
            ->limit(3, 4)
            ->getSql();
        echo $sql.'<br>'.PHP_EOL;
    }


}