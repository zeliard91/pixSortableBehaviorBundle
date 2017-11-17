<?php
/*
 * This file is part of the pixSortableBehaviorBundle.
 *
 * (c) Nicolas Ricci <nicolas.ricci@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pix\SortableBehaviorBundle\Services;

use Doctrine\Common\Util\ClassUtils;
use Redking\ParseBundle\ObjectManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PositionParseHandler extends PositionHandler
{
    /**
     * ObjectManager
     */
    protected $om;

    /**
     * @var array
     */
    private static $cacheLastPosition = [];

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->om = $objectManager;
    }

    /**
     * @param object $entity
     * @return int
     */
    public function getLastPosition($object)
    {
        $objectClass = ClassUtils::getClass($object);
        $parentObjectClass = true;
        while ($parentObjectClass)
        {
            $parentObjectClass = ClassUtils::getParentClass($objectClass);
            if ($parentObjectClass) {
                $reflection = new \ReflectionClass($parentObjectClass);
                if($reflection->isAbstract()) {
                    break;
                }
                $objectClass = $parentObjectClass;
            }
        }

        $groups      = $this->getSortableGroupsFieldByEntity($objectClass);

        $cacheKey = $this->getCacheKeyForLastPosition($object, $groups);

        if (!isset(self::$cacheLastPosition[$cacheKey])) {

            $positionFields = $this->getPositionFieldByEntity($objectClass);
            $result = $this->om
                ->createQueryBuilder($objectClass)
                ->sort($positionFields, 'desc')
                ->limit(1)
                ->getQuery()
                ->getSingleResult();

            if (null !== $result) {
                $accessor = PropertyAccess::createPropertyAccessor();
                self::$cacheLastPosition[$cacheKey] = $accessor->getValue($result, $positionFields);
            }

            self::$cacheLastPosition[$cacheKey] = 0;
        }

        return self::$cacheLastPosition[$cacheKey];
    }

    /**
     * @param object $object
     * @param array  $groups
     * @return string
     */
    private function getCacheKeyForLastPosition($object, $groups)
    {
        $cacheKey = ClassUtils::getClass($object);

        foreach ($groups as $groupName) {
            $getter = 'get' . $groupName;

            if ($object->$getter()) {
                $cacheKey .= '_' . $object->$getter()->getId();
            }
        }

        return $cacheKey;
    }
}
