<?php

namespace Ist1\ContentBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use \Twig_Environment as Twig;
use Doctrine\ORM\EntityManagerInterface;
use Ist1\ContentBundle\Entity\Content;

/**
 * Class ContentExtension
 * @package Ist1ContentBundle\Twig
 */
class ContentExtension extends \Twig_Extension
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RequestStack */
    private $requestStack;

    /**
     * @param EntityManagerInterface $entityManager
     * @param RequestStack $requestStack
     */
    function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function content($name, $default = 'no content')
    {
        /** @var Content $content */
        $content = $this->entityManager->getRepository('Ist1ContentBundle:Content')->findOneBy(['name' => $name]);

        if (!$content) {
            $content = new Content($name, $default);
        }

        /** @var \Gedmo\Translatable\Entity\Repository\TranslationRepository $translationRepo */
        $translationRepo = $this->entityManager->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $translationRepo->findTranslations($content);
        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        if (!array_key_exists($locale, $translations)) {
            $content->setTranslatableLocale($locale);
            $this->entityManager->persist($content);
            $this->entityManager->flush();
        }

        $content->setTranslatableLocale($locale);
        $this->entityManager->refresh($content);

        return $content->getContent();
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('content', [$this, 'content'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'content_extension';
    }
}