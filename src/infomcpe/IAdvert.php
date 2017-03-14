<?php

namespace infomcpe;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\Utils; 
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\scheduler\PluginTask;
use pocketmine\tile\Sign;

class IAdvert extends PluginBase implements Listener {
     const Prfix = '§f[§aIAdvert§f]§e ';
        public function onEnable(){
            $folder = $this->getDataFolder();
			if(!is_dir($folder))
				@mkdir($folder);
        if(!file_exists(folder.'data.json')){
            $this->saveResource('data.json');
        }
            $this->data = (new Config($folder.'data.json', Config::JSON))->getAll();
              $this->session = $this->getServer()->getPluginManager()->getPlugin("SessionAPI");
            if ($this->getServer()->getPluginManager()->getPlugin("PluginDownloader")) {
            //$this->getServer()->getScheduler()->scheduleAsyncTask(new CheckVersionTask($this, 335));
            
            if($this->session == NULL){
               if($this->getServer()->getPluginManager()->getPlugin("PluginDownloader")->getDescription()->getVersion() >= '1.4'){
                   $this->getServer()->getPluginManager()->getPlugin("PluginDownloader")->installByID('SessionAPI');
               }
            }
            $this->getServer()->getPluginManager()->registerEvents($this, $this);
        }
        }
         public function onCommand(CommandSender $player, Command $command, $label, array $args){
		switch($command->getName()){
                    case 'iadvert':
                        switch ($args[0]) {
                            case 'help':
                        $player->sendMessage("");
                                break;
                            case 'info':
                                $player->sendMessage(IAdvert::Prfix.'Плагин был написан Алексеем Лозовягиным спецеально для INFOMCPE.RU');
                                break;
                            case 'add':
                                $player->sendMessage(IAdvert::Prfix."Выберите тип объявления§f");
                                $id = 0;
                                foreach ($this->data['ads'] as $ads) {
                                    $id++;
                                    $player->sendMessage("{$id} §e-§f {$ads['name']} §aСтоймость§f: §e{$ads['value']}$ §eПример:§f {$ads['prefix']} объявление§f");
                                }
                                $this->session->createSession(strtolower($player->getName()), 'ad_stat',1);
                                $player->sendMessage("↑ - id. Пожалуйста напишите id для того чтобы выбрать");
                                break;
                            default:
                                if ($args != NULL){
                                    $player->sendMessage(IAdvert::Prfix."Cуб-команда не найдена");
                                }
                                break;
                        }
                        if(is_null($args[0])){
                            $player->sendMessage(IAdvert::Prfix.'§6/ad add - Создать объявление (будет отправлено всем игрокам сервера \n');
                        }
                }
         }
        public function onChat(PlayerChatEvent $event) {
            $player = $event->getPlayer();
            $message = $event->getMessage();
            $this->ads = $this->data;
            if($this->session->getSessionData(strtolower($player->getName()), 'ad_stat') == 1){
                if (is_numeric($message)) {
                      $this->session->createSession(strtolower($player->getName()), 'ad_id', $message);
                       $this->session->createSession(strtolower($player->getName()), 'ad_stat',2);
                      $player->sendMessage(IAdvert::Prfix."Успешно. ID получен теперь напишите текст объявления");
                       $event->setCancelled(); 
                } else {
                     $event->setCancelled(); 
                    $player->sendMessage(IAdvert::Prfix."Укажите id числом");
                }
            } elseif ($this->session->getSessionData(strtolower($player->getName()), 'ad_stat') == 2) {
                $this->session->createSession(strtolower($player->getName()), 'ad_text', $message);
                $this->session->createSession(strtolower($player->getName()), 'ad_stat', 3);
                $player->sendMessage(IAdvert::Prfix."Успешно. Текст получен. Ваше объявление будет размещено таким образом:§f");
                $id = $this->session->getSessionData(strtolower($player->getName()), 'ad_id') -1;
                $player->sendMessage($this->ads['ads'][$id]['prefix'].' '.$message);
                $player->sendMessage(IAdvert::Prfix.'Также с вашего счета, будет списано '.$this->ads['ads'][$id]['value'].'$');
                $player->sendMessage(Iadvert::Prfix.'Эсли хотите продолжить напишите "+", чтобы написать текст повторно "-". Для полной отмены, "--"');
             $event->setCancelled(); 
                
            } elseif ($this->session->getSessionData(strtolower($player->getName()), 'ad_stat') == 3) {
                if($message == '+'){
                    $id = $this->session->getSessionData(strtolower($player->getName()), 'ad_id') -1;
                    if($this->getMoney($player) >= $this->ads['ads'][$id]['value'] ){
                    foreach ($this->getServer()->getOnlinePlayers() as $players) {
                              $players->sendMessage($this->ads['ads'][$id]['prefix'].' '.$this->session->getSessionData(strtolower($player->getName()), 'ad_text'));
                         }
                          $event->setCancelled();
                          $this->reduceMoney($player, $this->ads['ads'][$id]['value']);
                          $this->session->createSession(strtolower($player->getName()), 'ad_stat', null);
                         $player->sendMessage(IAdvert::Prfix.'Успешно, отправлено');
                    } else {
                         $event->setCancelled(); 
                        $player->sendMessage(IAdvert::Prfix."Ошибка. На вашем счету не достаточно средств, нужно:".$this->ads['ads'][$id]['value'].'$');
                    }
                } else  if($message == '-'){
                     $event->setCancelled(); 
                    $this->session->createSession(strtolower($player->getName()), 'ad_stat',2);
                    $player->sendMessage('Успешно, напишите текст повторно');
                 }else if($message == '--'){
                      $event->setCancelled(); 
                    $this->session->createSession(strtolower($player->getName()), 'ad_stat', null);
                    $player->sendMessage('Успешно, Отменено');
                } else if($message != '+' && $message != '-' &&$message != '--'){
                    $player->sendMessage(Iadvert::Prfix.'Эсли хотите продолжить напишите "+", чтобы написать текст повторно "-". Для полной отмены, "--"');
             
                }
                
            }
        }
         private function getMoney($player) {
            if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
                $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){

               $money = EconomyPlus::getInstance()->getMoney($player);
               }
               return $money;
        }
        private function addMoney($player, $amount) {
           if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
                $result = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->addMoney($player, $amount);
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){
               $result = EconomyPlus::getInstance()->addMoney($player, $amount);
               }
               return $result;
        }
        private function reduceMoney($player, $amount) {
          if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
               $result = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->reduceMoney($player, $amount);
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){
               $result = EconomyPlus::getInstance()->reduceMoney($player, $amount);
               }
               return $result;
        }
        }