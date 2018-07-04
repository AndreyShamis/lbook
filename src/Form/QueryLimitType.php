<?php
/**
 * Created by PhpStorm.
 * User: werd
 * Date: 04/07/18
 * Time: 09:43
 */

namespace App\Form;

use App\Entity\LogBookVerdict;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QueryLimitType extends AbstractType
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $range = range(500,10000,500);
        $resolver->setDefaults([
            'choices' => array_combine($range, $range),
            'label' => 'Limit',
            'required' => true,
//            'placeholder' => 'N/A',
            'empty_data' => 2000,
            'choices_as_values' => true,

        ]);
    }

    /**
     * @return string
     */
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}