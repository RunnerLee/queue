<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

class RedisLuaScripts
{

    public static function migrate()
    {
        return <<<LUA
local val = redis.call('zrangebyscore', KEYS[1], '-inf', ARGV[1])
if (next(val) ~= nil) then
    redis.call('zremrangebyrank', KEYS[1], 0, #val - 1)
    for i = 1, #val, 100 do
        redis.call('rpush', KEYS[2], unpack(val, i, math.min(i + 99, #val)))
    end
end

return val
LUA;
    }

    public static function pop()
    {
        return <<<LUA
local job = redis.call('lpop', KEYS[1])
local reserved = false

if (job ~= false) then
    reserved = cjson.decode(job)
    reserved['attempts'] = reserved['attempts'] + 1
    reserved = cjson.encode(reserved)
    redis.call('zadd', KEYS[2], ARGV[1], reserved)
end

return {job, reserved}
LUA;
    }

}
