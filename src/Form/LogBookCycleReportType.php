<?php

namespace App\Form;

use App\Entity\LogBookCycleReport;
use App\Entity\StorageString;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookCycleReportType extends AbstractType
{
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
//            ->add('createdAt')
//            ->add('updatedAt')
            ->add('description')
//            ->add('period')
//            ->add('duration')
//            ->add('suitesCount')
//            ->add('testsCount')
//            ->add('testsPass')
//            ->add('testsFail')
//            ->add('testsError')
//            ->add('testsOther')
//            ->add('testsTotal')
//            ->add('platforms')
//            ->add('chips')
//            ->add('mode')
//            ->add('components')
//            ->add('creator')
//            ->add('defects')

//            ->add('build')
            ->add('platforms', ChoiceType::class, [
                    'required' => false,
                    'choice_label' => 'name',
                    'choice_value' => 'name',
                    'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'platforms'),
                    'multiple'=> true,
                    'attr' => [
                        'style' => 'width:400px;display: none;',
                        'class' => 'LogBookSelectablePlatformsType multiselect']
                ]
            )
            ->add('chips', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'choice_value' => 'name',
                'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'chips'),
                'multiple'=> true,
                'attr' => [
                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectableChipsType multiselect']
            ])
            ->add('components', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'choice_value' => 'name',
                'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'components'),
                'multiple'=> true,
                'attr' => [
                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectableChipsType multiselect']
            ])
            ->add('mode', ChoiceType::class, [
                'required' => false,
                'label' => ' ',
                'choices' => [
                    'regular_mode' => 'regular_mode',
                    'package_mode' => 'package_mode'
                ],
//              'choice_label' => 'TestingLevel',
//              'choice_value' => 'Id',
//              'expanded' => false, //             // false will convert to checkbox
                'multiple'=> false,
                'attr' => [
                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectablePackageModeType multiselect']
            ])
        ;
//        if ($builder->getData()->getCycles()->count() < 1) {
//            $builder->add('cycles');
//        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookCycleReport::class,
        ]);
    }
}
