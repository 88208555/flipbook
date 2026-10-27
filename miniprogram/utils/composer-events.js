const listeners=new Map();
module.exports={on(key,fn){if(!listeners.has(key))listeners.set(key,new Set());listeners.get(key).add(fn);return()=>{const set=listeners.get(key);if(set){set.delete(fn);if(!set.size)listeners.delete(key);}};},emit(key,value){for(const fn of listeners.get(key)||[])fn(value);}};
