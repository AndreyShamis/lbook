<?php
/**
 * Created by PhpStorm.
 * User: werd
 * Date: 01/07/18
 * Time: 13:38
 */

namespace App\Form;


use App\Entity\LogBookVerdict;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookSelectableVerdictType extends AbstractType
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return LogBookVerdict[]|array
     */
    public function getAllVerdicts(): array
    {
        $verdicts = $this->entityManager->getRepository(LogBookVerdict::class)->findAll();
        return $verdicts;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', ChoiceType::class,
                array(
                    'expanded' => true,             // false will conver to checkbox
                    'label' => 'Select verdict',
                    'choices' => $this->getAllVerdicts(),
                    'choice_label' => 'Name',
                    'choice_value' => 'Id',
                    'multiple'=> true,
                    'attr' => array('style' => 'width:400px;') // min-height:180px;
                )
            );
    }


    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            //'compound' => true,
            // uncomment if you want to bind to a class
            'data_class' => LogBookVerdict::class,
        ]);
    }

//    public function getParent()
//    {
//        return ChoiceType::class;
//    }
}