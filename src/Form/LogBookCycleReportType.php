<?php

namespace App\Form;

use App\Entity\LogBookCycle;
use App\Entity\LogBookCycleReport;
use App\Entity\StorageString;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
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

        try {
            $cycles = $builder->getData()->getCycles();
        } catch (\Throwable $ex) {

        }

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

            ->add('chips', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'choice_value' => 'name',
                'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'chips'),
                'multiple'=> true,
                'attr' => [
//                    'style' => 'width:400px;display: none;',
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
//                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectablePackageModeType multiselect']
            ])
        ;
        if ($cycles->count() < 1) {
            $builder->add('cycles')
                ->add('components', ChoiceType::class, [
                    'required' => false,
                    'choice_label' => 'name',
                    'choice_value' => 'name',
                    'choices' => $this->getComponentsFromCycles($cycles),
                    'multiple'=> true,
                    'attr' => [
//                    'style' => 'width:400px;display: none;',
                        'class' => 'LogBookSelectableChipsType multiselect']
                ])
                ->add('platforms', ChoiceType::class, [
                        'required' => false,
                        'choice_label' => 'name',
                        'choice_value' => 'name',
                        'choices' => $this->getPlatformsFromCycles($cycles),
                        'multiple'=> true,
                        'attr' => [
//                        'style' => 'width:400px;display: none;',
                            'class' => 'LogBookSelectablePlatformsType multiselect']
                    ]
                )
            ;
        } else {
            $builder->add('components', ChoiceType::class, [
                'required' => false,
                'choice_label' => 'name',
                'choice_value' => 'name',
                'choices' => $this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'components'),
                'multiple'=> true,
                'attr' => [
//                    'style' => 'width:400px;display: none;',
                    'class' => 'LogBookSelectableChipsType multiselect']
            ])
            ->add('platforms', ChoiceType::class, [
                    'required' => false,
//                    'choice_label' => 'name',
//                    'choice_value' => 'name',
                    'choices' => $this->getPlatformsFromCycles($cycles), //$this->entityManager->getRepository(StorageString::class)->findByKeys('lbk', 'suites', 'platforms'),
                    'multiple'=> true,
                    'attr' => [
//                        'style' => 'width:400px;display: none;',
                        'class' => 'LogBookSelectablePlatformsType multiselect']
                ]
            )
            ;
        }
    }

    /**
     * @param PersistentCollection $cycles
     * @return array
     */
    protected function getPlatformsFromCycles(PersistentCollection $cycles) {
        $ret = [];
        /** @var LogBookCycle $cycle */
        foreach ($cycles as $cycle) {
            $suites = $cycle->getSuiteExecution();
            foreach ($suites as $suite) {
                $comp = $suite->getPlatform();
                $ret[$comp] = $comp;
            }
        }
        return $ret;
    }

    /**
     * @param PersistentCollection $cycles
     * @return array
     */
    protected function getComponentsFromCycles(PersistentCollection $cycles) {
        $ret = [];
        /** @var LogBookCycle $cycle */
        foreach ($cycles as $cycle) {
            $suites = $cycle->getSuiteExecution();
            foreach ($suites as $suite) {
                $comps = $suite->getComponents();
                foreach ($comps as $comp) {
                    $tmp_arr = explode(',', $comp);
                    foreach ($tmp_arr as $tmp_comp) {
                        if (!in_array($tmp_comp, $ret)) {
                            $ret[] = $tmp_comp;
                        }
                    }
                }

            }
        }

        return $ret;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookCycleReport::class,
        ]);
    }
}
