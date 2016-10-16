<?php
namespace TNTBazooka;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\scheduler\CallbackTask;

class Main extends PluginBase implements Listener {
	public function onEnable() {
        	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onPacketReceive(DataPacketReceiveEvent $event){//パケットを受け取った時のeventです
	    	$packet = $event->getPacket();
	    	if($packet::NETWORK_ID == 0x1f){//もしパケットのidが0xifだったら...(IDはnetwork\protocol\Info.phpに載っています)
			$player = $event->getPlayer();
			if($event->getPlayer()->getInventory()->getItemInHand()->getID() == 283){//もし金の剣を手に持っていたら
				$tnt = Entity::createEntity("PrimedTNT", $player->getLevel()->getChunk($player->x >> 4, $player->z >> 4), new CompoundTag("", [//エンティティオブジェクト作成
					"Pos" => new ListTag("Pos", [//スポーンさせる場所
						new DoubleTag("", $player->x + 0.5),
						new DoubleTag("", $player->y + $player->getEyeHeight()),//$player->yは足元なので目の高さを足します
						new DoubleTag("", $player->z + 0.5)
					]),
					"Motion" => new ListTag("Motion", [//どう動かすか(ココらへんの計算は高校生で三角関数を習っていても難しいと思われるので省かせて頂きますm(__)m)
						new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
						new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
						new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
					]),
					"Rotation" => new ListTag("Rotation", [
						new FloatTag("", $player->yaw),
						new FloatTag("", $player->pitch)
					]),
					"Fuse" => new ByteTag("Fuse", 2)//スポーンさせてから何tick後に爆発させるか
				]), false);

				$tnt->spawnToAll();//TNTをスポーンさせます
				$tnt->setMotion($tnt->getMotion()->multiply(5));//飛ぶ速さをセットします
				$this->TNT[$player->getName()] = 4;//連続で飛ばすTNTの数をセットします
				$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"TNTTask"], [$player]), 2);//2tick後にTNTTaskに$playerを渡します
			}
		}
	}
	public function TNTTask($player){
		if($this->TNT[$player->getName()] > 0){
			$tnt = Entity::createEntity("PrimedTNT", $player->getLevel()->getChunk($player->x >> 4, $player->z >> 4), new CompoundTag("", [//上と同じ感じです
				"Pos" => new ListTag("Pos", [
					new DoubleTag("", $player->x + 0.5),
					new DoubleTag("", $player->y + $player->getEyeHeight()),
					new DoubleTag("", $player->z + 0.5)
				]),
				"Motion" => new ListTag("Motion", [
					new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
					new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
					new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
				]),
				"Rotation" => new ListTag("Rotation", [
					new FloatTag("", $player->yaw),
					new FloatTag("", $player->pitch)
				]),
				"Fuse" => new ByteTag("Fuse", 2)
			]), false);

			$tnt->spawnToAll();
			$tnt->setMotion($tnt->getMotion()->multiply(5));
			$this->TNT[$player->getName()]--;
			$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"TNTTask"], [$player]), 2);//次のTNTを発射させるために
		}
	}
}
